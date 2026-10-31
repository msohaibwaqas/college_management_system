<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\Datesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TestAttendanceController extends Controller
{
    /**
     * Show the attendance marking form
     */
    public function create()
    {
        $isTeacher = Auth::check() && Auth::user()->isTeacher();
        $staff = $isTeacher ? Auth::user()->staff : null;

        $query = Datesheet::with(['subject'])
            ->select('id', 'test_date', 'admission_year', 'subject_id', 'exam_name')
            ->orderBy('test_date');

        if ($isTeacher) {
            if ($staff) {
                $query->whereIn('subject_id', function ($q) use ($staff) {
                    $q->select('subject_id')
                      ->from('student_teacher_subjects')
                      ->where('staff_id', $staff->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $allDatesheets = $query->get();

        // Group by exam_name
        $grouped = $allDatesheets->groupBy(function ($item) {
            return $item->exam_name ?: 'Other / Unassigned';
        });

        $examGroups = [];
        foreach ($grouped as $name => $items) {
            $dates = $items->groupBy(fn ($i) => $i->test_date->format('Y-m-d'));
            $minDate = $items->min('test_date');
            $maxDate = $items->max('test_date');

            $rangeStr = '';
            if ($minDate && $maxDate) {
                $rangeStr = $minDate->format('d M Y') === $maxDate->format('d M Y')
                    ? $minDate->format('d M Y')
                    : $minDate->format('d M Y') . ' – ' . $maxDate->format('d M Y');
            }

            $dateList = [];
            foreach ($dates as $dateKey => $dateItems) {
                $dateList[] = [
                    'date_key' => $dateKey,
                    'formatted' => $dateItems->first()->test_date->format('l, d F Y'),
                    'test_count' => $dateItems->count(),
                ];
            }

            $isOther = ($name === 'Other / Unassigned');

            $examGroups[] = [
                'name' => $name,
                'key' => $name,
                'is_other' => $isOther,
                'display_label' => $isOther ? $name : "$name ($rangeStr)",
                'start_date' => $minDate ? $minDate->format('Y-m-d') : null,
                'end_date' => $maxDate ? $maxDate->format('Y-m-d') : null,
                'dates' => $dateList,
                'total_tests' => $items->count(),
            ];
        }

        usort($examGroups, function ($a, $b) {
            if ($a['is_other'] !== $b['is_other']) {
                return $a['is_other'] ? 1 : -1;
            }
            return strcmp($b['end_date'] ?? '', $a['end_date'] ?? '');
        });

        $testDates = $allDatesheets->groupBy(function ($item) {
            return $item->test_date->format('Y-m-d');
        })->map(function ($group) {
            return [
                'date' => $group->first()->test_date,
                'tests' => $group
            ];
        });

        return view('test-attendance.create', compact('examGroups', 'testDates'));
    }

    /**
     * Fetch students for attendance
     */
    public function fetchStudents(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|integer',
            'class_level'   => 'required|in:11,12',
            'subject_id'    => 'required|exists:subjects,id',
            'exam_type'     => 'required|string',
            'exam_name'     => 'nullable|string',
            'test_date'     => 'nullable|date',
        ]);

        $year = $request->academic_year;
        $classLevel = $request->class_level;
        $subjectId = $request->subject_id;
        
        $subject = Subject::findOrFail($subjectId);

        // Determine teacher restriction
        $isTeacher = Auth::check() && Auth::user()->isTeacher();

        $testDate = $request->input('test_date');
        if (!$testDate) {
            $testDate = Datesheet::where('subject_id', $subjectId)
                ->where('admission_year', $year)
                ->where(function ($q) use ($request) {
                    if ($request->filled('exam_name')) {
                        $q->where('exam_name', $request->exam_name);
                    }
                })
                ->value('test_date')?->format('Y-m-d');
        }

        $studentsQuery = Student::with(['admissions', 'father'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->select('students.*', 'admissions.college_roll_no');

        $studentsQuery->whereNotIn('admissions.status', ['Graduated', 'graduated']);

        if ($isTeacher) {
            $staff = Auth::user()->staff;
            if (!$staff) {
                return response()->json(['error' => 'Teacher record not found.'], 403);
            }
            // Filter students assigned to this teacher for this subject
            $studentsQuery->whereHas('studentTeacherSubjects', function($q) use ($staff, $subjectId) {
                $q->where('staff_id', $staff->id)
                  ->where('subject_id', $subjectId);
            });
        }

        if ($subject->type === 'Elective') {
            $studentsQuery->whereHas('electives', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        } else {
            // Compulsory subject — only show students assigned this specific compulsory subject
            $studentsQuery->whereHas('compulsorySubjects', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        }
        
        $students = $studentsQuery->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')->get();

        // Identify students who were struck off on this test date
        $struckOffStudentIds = [];
        if ($testDate) {
            $struckOffStudentIds = DB::table('student_struck_off_logs')
                ->whereIn('student_id', $students->pluck('id'))
                ->whereDate('struck_off_at', '<=', $testDate)
                ->where(function ($sub) use ($testDate) {
                    $sub->whereNull('readmitted_at')
                        ->orWhereDate('readmitted_at', '>', $testDate);
                })
                ->pluck('student_id')
                ->toArray();
        }

        foreach ($students as $st) {
            $st->is_struck_off = in_array($st->id, $struckOffStudentIds);
        }

        // Check for existing results to pre-fill attendance
        $existingResults = Result::where('academic_year', $year)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('exam_type', $request->exam_type)
            ->where('exam_name', $request->exam_name ?? $request->exam_type)
            ->get()
            ->keyBy('student_id');

        // If a test date is provided, check lecture attendances for any student missing from results
        $lectureAttendances = collect();
        if ($request->filled('test_date')) {
            $lectureAttendances = Attendance::where('subject_id', $subjectId)
                ->whereDate('attendance_date', $request->test_date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        foreach ($students as $student) {
            if (in_array($student->id, $struckOffStudentIds)) {
                $existing = $existingResults->get($student->id);
                $existingResults->put($student->id, (object) [
                    'student_id'         => $student->id,
                    'is_absent'          => 1,
                    'obtained_theory'    => 0,
                    'obtained_practical' => 0,
                    'total_marks'        => $existing?->total_marks ?? null,
                    'is_struck_off'      => true,
                ]);
            } elseif (!$existingResults->has($student->id) && $lectureAttendances->has($student->id)) {
                $att = $lectureAttendances->get($student->id);
                $isAbsent = in_array($att->status, ['absent', 'leave']);
                $existingResults->put($student->id, (object) [
                    'student_id'         => $student->id,
                    'is_absent'          => $isAbsent ? 1 : 0,
                    'obtained_theory'    => $isAbsent ? 0 : null,
                    'obtained_practical' => $isAbsent ? 0 : null,
                    'total_marks'        => null,
                    'is_struck_off'      => false,
                ]);
            }
        }

        return response()->json([
            'students'         => $students,
            'existing_results' => $existingResults,
            'subject'          => $subject
        ]);
    }

    /**
     * Store attendance
     */
    public function store(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|integer',
            'class_level' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|string',
            'exam_name' => 'required|string',
            'old_exam_type' => 'nullable|string',
            'old_exam_name' => 'nullable|string',
            'total_marks' => 'required|integer|min:1', 
            'attendance' => 'nullable|array', // Array of student_ids marked as absent
            'test_date' => 'nullable|date',
        ]);

        $year = $request->academic_year;
        $classLevel = $request->class_level;
        $subjectId = $request->subject_id;
        $examType = $request->exam_type;
        $examName = $request->exam_name;
        $oldExamType = $request->old_exam_type;
        $oldExamName = $request->old_exam_name;

        // Perform rename if identifiers changed
        if ($oldExamName && ($oldExamName !== $examName || $oldExamType !== $examType)) {
            Result::where([
                'academic_year' => $year,
                'class_level' => $classLevel,
                'subject_id' => $subjectId,
                'exam_type' => $oldExamType,
                'exam_name' => $oldExamName,
            ])->update([
                'exam_type' => $examType,
                'exam_name' => $examName,
                'updated_by' => Auth::id(),
            ]);
        }

        // RE-FETCH ALL STUDENTS to ensure we have the complete list for this subject/class/year
        // This logic mirrors fetchStudents but we need the IDs to iterate over.
        
        $year = $request->academic_year;
        $subjectId = $request->subject_id;
        $subject = Subject::find($subjectId); // Assuming valid as per validation

        // Teacher restriction check (same as fetchStudents)
        $isTeacher = Auth::check() && Auth::user()->isTeacher();

        $testDate = $request->input('test_date');
        if (!$testDate) {
            $testDate = Datesheet::where('subject_id', $subjectId)
                ->where('admission_year', $year)
                ->where(function ($q) use ($examName) {
                    if ($examName) {
                        $q->where('exam_name', $examName);
                    }
                })
                ->value('test_date')?->format('Y-m-d');
        }

        $studentsQuery = Student::join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->whereNotIn('admissions.status', ['Graduated', 'graduated'])
            ->select('students.id');

        if ($isTeacher) {
            $staff = Auth::user()->staff;
            if (!$staff) {
                return response()->json(['error' => 'Teacher record not found.'], 403);
            }
            $studentsQuery->whereHas('studentTeacherSubjects', function($q) use ($staff, $subjectId) {
                $q->where('staff_id', $staff->id)
                  ->where('subject_id', $subjectId);
            });
        }

        if ($subject && $subject->type === 'Elective') {
            $studentsQuery->whereHas('electives', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        } else {
            // Compulsory subject — only include students assigned this specific compulsory subject
            $studentsQuery->whereHas('compulsorySubjects', function($q) use ($subjectId) {
                $q->where('subject_id', $subjectId);
            });
        }
        
        $allStudentIds = $studentsQuery->pluck('id')->toArray();
        $absentStudentIds = array_keys($request->attendance ?? []);

        // Automatically mark struck-off students for this test date as absent
        if ($testDate) {
            $struckOffOnDate = DB::table('student_struck_off_logs')
                ->whereIn('student_id', $allStudentIds)
                ->whereDate('struck_off_at', '<=', $testDate)
                ->where(function ($sub) use ($testDate) {
                    $sub->whereNull('readmitted_at')
                        ->orWhereDate('readmitted_at', '>', $testDate);
                })
                ->pluck('student_id')
                ->toArray();

            $absentStudentIds = array_unique(array_merge($absentStudentIds, $struckOffOnDate));
        }
        
        DB::transaction(function () use ($request, $allStudentIds, $absentStudentIds) {
            foreach ($allStudentIds as $studentId) {
                $isAbsent = in_array($studentId, $absentStudentIds);
                
                // Check if result already exists to preserve existing marks if updating
                // But for attendance, we strictly enforce the Absent status.
                // If marks exist and we mark absent -> marks become 0? Yes, usually.
                // If marks exist and we mark Present (uncheck absent) -> keep marks? 
                // Or if creating new, obtained_theory is null.
                
                $data = [
                    'total_marks' => $request->total_marks,
                    'is_absent' => $isAbsent ? 1 : 0,
                    'updated_by' => Auth::id(),
                ];
                
                if ($isAbsent) {
                    $data['obtained_theory'] = 0;
                    $data['obtained_practical'] = 0;
                }
                
                // We use updateOrCreate. 
                // If creating (new record):
                //   - Absent: 0 marks.
                //   - Present: marks should be null (so they show empty in marks entry), except total_marks.
                // If updating:
                //   - Absent: Overwrite marks to 0.
                //   - Present: If it was absent, we might want to reset marks to null? 
                //     Or if it had marks, keep them?
                //     Safest is: If we are UNMARKING absent, we don't know the marks. 
                //     But we shouldn't overwrite existing valid marks with null if they exist.
                
                // Let's retrieve first to be safe about not wiping marks of present students
                // Optimization: fetch all results first? No, simple loop for now.
                
                // Actually, standard updateOrCreate doesn't support "update only if null".
                // So we can do this:
                
                $search = [
                    'student_id' => $studentId,
                    'subject_id' => $request->subject_id,
                    'class_level' => $request->class_level,
                    'exam_type' => $request->exam_type,
                    'exam_name' => $request->exam_name,
                    'academic_year' => $request->academic_year,
                ];
                
                $result = Result::where($search)->first();
                
                if ($result) {
                    if ($isAbsent) {
                        $result->update([
                            'total_marks' => $request->total_marks,
                            'is_absent' => 1,
                            'obtained_theory' => 0,
                            'obtained_practical' => 0,
                            'updated_by' => Auth::id()
                        ]);
                    } else {
                        // Present
                        // Just update total_marks and absent status (and created_by/updated_by)
                        // Don't wipe marks.
                        $result->update([
                            'total_marks' => $request->total_marks,
                            'is_absent' => 0,
                            'updated_by' => Auth::id()
                        ]);
                    }
                } else {
                    // Create new
                    Result::create(array_merge($search, [
                        'total_marks' => $request->total_marks,
                        'is_absent' => $isAbsent ? 1 : 0,
                        'obtained_theory' => $isAbsent ? 0 : null,
                        'obtained_practical' => $isAbsent ? 0 : null,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id()
                    ]));
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Attendance saved successfully!']);
    }
}
