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

use App\Services\SeatingPlanService;

class SeatingPlanController extends Controller
{
    public function __construct(
        protected SeatingPlanService $seatingPlanService
    ) {}

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

        $candidates = $this->seatingPlanService->getHsscCandidatesForDate($date);

        if ($candidates->isEmpty()) {
            return back()->withErrors(['test_date' => 'No students found for the scheduled tests on this date.']);
        }

        $rooms = Room::where('is_active', true)->forExams()->orderBy('name')->get();
        if ($rooms->isEmpty()) {
            return back()->withErrors(['error' => 'No active rooms available.']);
        }

        DB::transaction(function () use ($date, $candidates, $rooms) {
            $plan = SeatingPlan::create([
                'test_date' => $date,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->seatingPlanService->allocateSeats(
                $candidates,
                $rooms,
                function ($studentData, $room, $seatNo) use ($plan) {
                    SeatingPlanDetail::create([
                        'seating_plan_id' => $plan->id,
                        'room_id' => $room->id,
                        'student_id' => $studentData['student_id'],
                        'subject_id' => $studentData['subject_id'],
                        'seat_no' => $seatNo,
                        'admission_year' => $studentData['admission_year'],
                    ]);
                }
            );
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
