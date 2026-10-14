<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Admissions;
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
        // Get all scheduled test dates from datesheets
        // We want to show tests so the user can pick one to mark attendance for.
        // Similar to ResultController::create
        
        $query = Datesheet::with(['subject'])
            ->select('test_date', 'admission_year', 'subject_id')
            ->orderBy('test_date');

        // Logic for filtering by teacher if necessary?
        // "user select Test Date and then Select Test"
        // If the user is a teacher, they should strictly see their own subjects?
        // Assuming yes for consistency.
        
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        if ($isTeacher) {
            $staff = Auth::user()->staff;
            if ($staff) {
                $query->whereIn('subject_id', function($q) use ($staff) {
                    $q->select('subject_id')
                      ->from('student_teacher_subjects')
                      ->where('staff_id', $staff->id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $testDates = $query->get()
            ->groupBy(function($item) {
                return $item->test_date->format('Y-m-d');
            })
            ->map(function($group) {
                return [
                    'date' => $group->first()->test_date,
                    'tests' => $group
                ];
            });

        return view('test-attendance.create', compact('testDates'));
    }

    /**
     * Fetch students for attendance
     */
    public function fetchStudents(Request $request)
    {
        $request->validate([
            'academic_year' => 'required|integer',
            'class_level' => 'required|in:11,12',
            'subject_id' => 'required|exists:subjects,id',
            'exam_type' => 'required|string',
            'exam_name' => 'nullable|string',
        ]);

        $year = $request->academic_year;
        $classLevel = $request->class_level;
        $subjectId = $request->subject_id;
        
        $subject = Subject::findOrFail($subjectId);

        // Determine teacher restriction
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        $studentsQuery = Student::with(['admissions', 'father'])
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->select('students.*', 'admissions.college_roll_no');

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

        // Check for existing results to pre-fill attendance
        $existingResults = Result::where('academic_year', $year)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('exam_type', $request->exam_type)
            ->where('exam_name', $request->exam_name ?? $request->exam_type)
            ->get()
            ->keyBy('student_id');

        return response()->json([
            'students' => $students,
            'existing_results' => $existingResults,
            'subject' => $subject
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
        $isTeacher = false;
        $activeRole = session('active_role');
        if ($activeRole) {
            $isTeacher = ($activeRole === 'teacher');
        } else {
            $isTeacher = Auth::user()->hasRole('teacher') && 
                        !Auth::user()->hasRole('admin') && 
                        !Auth::user()->hasRole('principal') && 
                        !Auth::user()->hasRole('clerk') &&
                        !Auth::user()->is_super_admin;
        }

        $studentsQuery = Student::join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
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
