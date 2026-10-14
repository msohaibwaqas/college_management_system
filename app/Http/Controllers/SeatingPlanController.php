<?php

namespace App\Http\Controllers;

use App\Models\Datesheet;
use App\Models\Room;
use App\Models\SeatingPlan;
use App\Models\SeatingPlanDetail;
use App\Models\Student;
use App\Models\Admissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatingPlanController extends Controller
{
    public function index()
    {
        $plans = SeatingPlan::orderByDesc('test_date')->get();
        return view('seating_plans.index', compact('plans'));
    }

    public function create()
    {
        // Get dates that have tests scheduled but no seating plan yet
        $scheduledDates = Datesheet::select('test_date')
            ->distinct()
            ->orderBy('test_date')
            ->pluck('test_date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date);
            });

        $existingPlanDates = SeatingPlan::pluck('test_date')
            ->map(function($date) {
                return $date->format('Y-m-d');
            });

        $availableDates = $scheduledDates->filter(function($date) use ($existingPlanDates) {
            return !$existingPlanDates->contains($date->format('Y-m-d'));
        });

        return view('seating_plans.create', compact('availableDates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'test_date' => 'required|date',
        ]);

        $date = $request->test_date;

        // Check if plan exists
        if (SeatingPlan::where('test_date', $date)->exists()) {
            return back()->withErrors(['test_date' => 'Seating plan for this date already exists.']);
        }

        // 1. Get Tests for this date
        $tests = Datesheet::whereDate('test_date', $date)->get();

        if ($tests->isEmpty()) {
            return back()->withErrors(['test_date' => 'No tests scheduled for this date.']);
        }

        // 2. Get Students
        // We need to gather all students who have exams on this day.
        // We'll reuse the logic from DatesheetController to find students for a subject/batch.
        // Since I can't easily call that private method, I'll replicate the query logic here.
        
        $studentList = collect(); // Collection of ['student_id', 'subject_id', 'admission_year']

        foreach ($tests as $test) {
            $subjectId = $test->subject_id;
            $year = $test->admission_year;
            $subject = $test->subject;

            // Get Student IDs and Staff IDs
            $query = Student::join('admissions', 'students.id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->whereIn('admissions.status', ['Admitted', 'Active'])
                ->leftJoin('student_teacher_subjects', function($join) use ($subjectId) {
                    $join->on('students.id', '=', 'student_teacher_subjects.student_id')
                         ->where('student_teacher_subjects.subject_id', '=', $subjectId)
                         ->whereNull('student_teacher_subjects.deleted_at')
                         ->on('student_teacher_subjects.class_level', '=', 'admissions.current_class');
                });

            if ($subject->type === 'Compulsory') {
                $query->whereHas('compulsorySubjects', function($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            } else {
                $query->whereHas('electives', function($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId);
                });
            }

            $studentsData = $query->select('students.id', 'student_teacher_subjects.staff_id')->get();

            foreach ($studentsData as $data) {
                // Avoid duplicates if student has 2 exams (rare but possible)
                if (!$studentList->contains('student_id', $data->id)) {
                    $studentList->push([
                        'student_id' => $data->id,
                        'subject_id' => $subjectId,
                        'admission_year' => $year,
                        'staff_id' => $data->staff_id,
                    ]);
                }
            }
        }


        if ($studentList->isEmpty()) {
            return back()->withErrors(['test_date' => 'No students found for the scheduled tests.']);
        }

        // Sort students: Year ASC, Subject ASC, Teacher ASC, Student ID ASC
        $studentList = $studentList->sort(function ($a, $b) {
            // 1. Admission Year (Earliest First)
            if ($a['admission_year'] !== $b['admission_year']) {
                return $a['admission_year'] <=> $b['admission_year'];
            }

            // 2. Subject
            if ($a['subject_id'] !== $b['subject_id']) {
                return $a['subject_id'] <=> $b['subject_id'];
            }

            // 3. Teacher
            if ($a['staff_id'] !== $b['staff_id']) {
                // Put students with no teacher at the end
                if ($a['staff_id'] === null) return 1;
                if ($b['staff_id'] === null) return -1;
                return $a['staff_id'] <=> $b['staff_id'];
            }

            // 4. Student ID (Unique seat number)
            return $a['student_id'] <=> $b['student_id'];
        });

        // 3. Get Rooms
        $rooms = Room::where('is_active', true)->forExams()->orderBy('name')->get();
        
        if ($rooms->isEmpty()) {
            return back()->withErrors(['error' => 'No active rooms available.']);
        }

        // 4. Distribute
        DB::transaction(function () use ($date, $studentList, $rooms) {
            $plan = SeatingPlan::create([
                'test_date' => $date,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $studentIndex = 0;
            $totalStudents = $studentList->count();
            $studentsArray = $studentList->values()->all();

            foreach ($rooms as $room) {
                if ($studentIndex >= $totalStudents) break;

                $capacity = $room->capacity;
                $seatedCount = 0;

                while ($seatedCount < $capacity && $studentIndex < $totalStudents) {
                    $studentData = $studentsArray[$studentIndex];
                    
                    SeatingPlanDetail::create([
                        'seating_plan_id' => $plan->id,
                        'room_id' => $room->id,
                        'student_id' => $studentData['student_id'],
                        'subject_id' => $studentData['subject_id'],
                        'seat_no' => $seatedCount + 1,
                        'admission_year' => $studentData['admission_year'],
                    ]);

                    $studentIndex++;
                    $seatedCount++;
                }
            }
        });

        return redirect()->route('seating-plans.index')->with('success', 'Seating plan generated successfully.');
    }

    public function show(SeatingPlan $seatingPlan)
    {
        $seatingPlan->load(['details.student.admissions', 'details.room', 'details.subject', 'invigilatorAssignments.staff.designationModel']);

        // Group by Room
        $rooms = $seatingPlan->details->groupBy('room_id');
        
        // Prepare data structure for view
        $data = [];
        foreach ($rooms as $roomId => $details) {
            $room = $details->first()->room;
            
            // Group by class instead of admission year (skip deleted students here, handle in view)
            $studentsByClass = $details->groupBy(function($detail) {
                if (!$detail->student) return 'Deleted/Vacant';
                
                $admission = $detail->student->admissions()
                    ->where('admission_year', $detail->admission_year)
                    ->first();
                return $admission->current_class ?? 'Unknown';
            });
            
            // Sort classes (11 before 12, Deleted last)
            $studentsByClass = $studentsByClass->sortKeys();
            
            // Sort students within each class by college roll number
            $studentsByClass = $studentsByClass->map(function($students) {
                return $students->sortBy(function($detail) {
                    if (!$detail->student) return 'ZZZZ999999';
                    
                    $admission = $detail->student->admissions()
                        ->where('admission_year', $detail->admission_year)
                        ->first();
                    return $admission->college_roll_no ?? 'ZZZ' . $detail->student->id;
                });
            });
            
            // Calculate subject counts for this room
            $subjectCounts = $details->groupBy('subject_id')->map(function($subjectDetails) {
                return [
                    'name' => $subjectDetails->first()->subject->name ?? 'N/A',
                    'count' => $subjectDetails->count()
                ];
            });

            $data[] = [
                'room_name' => $room->name,
                'students_by_class' => $studentsByClass,
                'subject_counts' => $subjectCounts,
                'invigilators' => $seatingPlan->invigilatorAssignments->where('room_id', $roomId)
            ];
        }

        return view('seating_plans.show', compact('seatingPlan', 'data'));
    }

    public function attendanceSheet(SeatingPlan $seatingPlan)
    {
        $seatingPlan->load(['details.student.admissions', 'details.student.studentTeacherSubjects.staff', 'details.room', 'details.subject', 'invigilatorAssignments.staff.designationModel']);

        // Group by Room
        $rooms = $seatingPlan->details->groupBy('room_id');
        
        // Prepare data structure for view
        $data = [];
        foreach ($rooms as $roomId => $details) {
            $room = $details->first()->room;
            
            // Sort students by college roll number and attach teacher name
            $students = $details->sortBy(function($detail) {
                if (!$detail->student) return 'ZZZZ999999';
                
                $admission = $detail->student->admissions()
                    ->where('admission_year', $detail->admission_year)
                    ->first();
                return $admission->college_roll_no ?? 'ZZZ' . $detail->student->id;
            })->map(function($detail) {
                if (!$detail->student) {
                    $detail->teacher_name = $detail->subject->name ?? 'N/A';
                    return $detail;
                }
                
                // Find teacher for this subject
                $teacherSubject = $detail->student->studentTeacherSubjects
                    ->where('subject_id', $detail->subject_id)
                    ->first();
                
                $detail->teacher_name = $teacherSubject && $teacherSubject->staff 
                    ? ($teacherSubject->staff->first_name . ' ' . $teacherSubject->staff->last_name) 
                    : ($detail->subject->title ?? $detail->subject->name);
                
                return $detail;
            })->values();
            
            $data[] = [
                'room_name' => $room->name,
                'students' => $students,
                'total_students' => $students->count(),
                'invigilators' => $seatingPlan->invigilatorAssignments->where('room_id', $roomId)
            ];
        }

        // Get institute settings for logo and name
        $instituteSettings = \App\Models\InstituteSetting::first();

        return view('seating_plans.attendance_sheet', compact('seatingPlan', 'data', 'instituteSettings'));
    }

    public function destroy(SeatingPlan $seatingPlan)
    {
        $seatingPlan->delete();
        return redirect()->route('seating-plans.index')->with('success', 'Seating plan deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:seating_plans,id',
        ]);

        SeatingPlan::whereIn('id', $request->ids)->delete();

        return redirect()->route('seating-plans.index')->with('success', 'Selected seating plans deleted successfully.');
    }
}
