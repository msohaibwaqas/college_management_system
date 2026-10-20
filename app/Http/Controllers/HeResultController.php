<?php

namespace App\Http\Controllers;

use App\Models\HeResult;
use App\Models\HeAdmission;
use App\Models\BsSubject;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeResultController extends Controller
{
    /**
     * Display a listing of HE results
     */
    public function index(Request $request)
    {
        $query = HeResult::with(['heAdmission.student', 'heAdmission.program', 'bsSubject', 'lockedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by program
        if ($request->filled('program_id')) {
            $query->forProgram($request->program_id);
        }

        // Filter by semester
        if ($request->filled('semester_no')) {
            $query->forSemester($request->semester_no);
        }

        // Filter by exam type
        if ($request->filled('exam_type')) {
            $query->forExamType($request->exam_type);
        }

        // Filter by academic year
        if ($request->filled('academic_year')) {
            $query->forAcademicYear($request->academic_year);
        }

        // Filter by locked status
        if ($request->filled('locked')) {
            if ($request->locked === 'locked') {
                $query->locked();
            } else {
                $query->unlocked();
            }
        }

        $results = $query->paginate(25);

        $programs = Program::orderBy('name')->get();
        $academicYears = HeAdmission::distinctAdmissionYears();

        return view('he.results.index', compact('results', 'programs', 'academicYears'));
    }

    /**
     * Show the form for creating new results
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdministrativeStaff();
        $isTeacher = $user->isTeacher();
        $staff = $user->staff;

        if ($isTeacher && !$staff) {
            return redirect()->route('dashboard')->with('error', 'No staff record found.');
        }

        if ($isTeacher) {
            $programs = Program::whereHas('heAdmissions.teacherAssignments', function ($query) use ($staff) {
                $query->where('staff_id', $staff->id);
            })->orderBy('name')->get();
        } else {
            $programs = Program::orderBy('name')->get();
        }

        $academicYears = HeAdmission::distinctAdmissionYears();

        // Pre-selected values from dashboard
        $preSelected = [
            'program_id' => $request->program_id,
            'semester_no' => $request->semester_no,
            'bs_subject_id' => $request->bs_subject_id,
            'academic_year' => $request->academic_year,
            'exam_type' => $request->exam_type,
        ];

        return view('he.results.create', compact('programs', 'academicYears', 'preSelected', 'isTeacher'));
    }

    /**
     * Fetch students for marks entry (AJAX)
     */
    public function fetchStudents(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'exam_type' => 'required|in:Mid,Sessional,Final',
            'academic_year' => 'required|integer',
        ]);

        $programId = $request->program_id;
        $semesterNo = $request->semester_no;
        $subjectId = $request->bs_subject_id;
        $examType = $request->exam_type;
        $academicYear = $request->academic_year;

        // Get the specific subject
        $subject = BsSubject::findOrFail($subjectId);

        $user = Auth::user();
        $isAdmin = $user->isAdministrativeStaff();
        $isTeacher = $user->isTeacher();
        $staff = $user->staff;

        // Security check for teachers
        if ($isTeacher) {
            $isAssigned = \App\Models\HeTeacherAssignment::where('staff_id', $staff->id)
                ->where('bs_subject_id', $subjectId)
                ->whereHas('heAdmission', function ($q) use ($programId, $semesterNo, $academicYear) {
                    $q->where('program_id', $programId)
                      ->where('current_semester', '>=', $semesterNo)
                      ->where('admission_year', $academicYear);
                })
                ->exists();

            if (!$isAssigned) {
                return response()->json(['message' => 'You are not assigned to this class/subject.'], 403);
            }

            if ($examType === 'Final') {
                return response()->json(['message' => 'Teachers can only enter Mid and Sessional marks.'], 403);
            }
        }

        // Get students enrolled in this program and semester
        $studentsQuery = HeAdmission::with('student')
            ->where('program_id', $programId)
            ->where('current_semester', '>=', $semesterNo)
            ->where('admission_year', $academicYear)
            ->whereIn('status', ['Active', 'Admitted']);

        // If teacher, they only see their assigned students for this subject
        if ($isTeacher) {
            $studentsQuery->whereHas('teacherAssignments', function ($q) use ($staff, $subjectId) {
                $q->where('staff_id', $staff->id)->where('bs_subject_id', $subjectId);
            });
        }

        $students = $studentsQuery->orderBy('roll_no')->get();

        // Get existing results for these students AND this specific subject
        $existingResults = HeResult::where('semester_no', $semesterNo)
            ->where('bs_subject_id', $subjectId)
            ->where('exam_type', $examType)
            ->where('academic_year', $academicYear)
            ->whereIn('he_admission_id', $students->pluck('id'))
            ->get()
            ->keyBy('he_admission_id'); // Key by admission ID since one subject per student

        // Check if user can edit (for locked results)
        $user = Auth::user();
        $canEditLocked = $user->hasAnyRole(['admin', 'principal']);

        return response()->json([
            'students' => $students,
            'subject' => $subject,
            'existingResults' => $existingResults,
            'canEditLocked' => $canEditLocked,
            'html' => view('he.results._students_table', compact(
                'students', 'subject', 'existingResults', 'examType', 'canEditLocked'
            ))->render(),
        ]);
    }

    /**
     * Store newly created results
     */
    public function store(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'exam_type' => 'required|in:Mid,Sessional,Final',
            'academic_year' => 'required|integer',
            'marks' => 'required|array',
            'marks.*.he_admission_id' => 'required|exists:he_admissions,id',
            'marks.*.bs_subject_id' => 'required|exists:bs_subjects,id',
        ]);

        $user = Auth::user();
        $isAdmin = $user->isAdministrativeStaff();
        $isTeacher = $user->isTeacher();

        if ($isTeacher && $request->exam_type === 'Final') {
            return response()->json(['success' => false, 'message' => 'Teachers can only enter Mid and Sessional marks.'], 403);
        }

        $userId = $user->id;
        $canEditLocked = $user->hasAnyRole(['admin', 'principal']);

        DB::beginTransaction();
        try {
            $savedCount = 0;
            $lockedSkipped = 0;

            foreach ($request->marks as $markData) {
                // Check if result already exists
                $existing = HeResult::where('he_admission_id', $markData['he_admission_id'])
                    ->where('bs_subject_id', $markData['bs_subject_id'])
                    ->where('semester_no', $request->semester_no)
                    ->where('exam_type', $request->exam_type)
                    ->first();

                if ($existing) {
                    // Check if locked and user cannot edit
                    if ($existing->is_locked && !$canEditLocked) {
                        $lockedSkipped++;
                        continue;
                    }

                    // Update existing
                    $isAbsent = $markData['is_absent'] ?? false;
                    $theory = $isAbsent ? 0 : ($markData['theory_marks'] ?? null);
                    $practical = $isAbsent ? 0 : ($markData['practical_marks'] ?? null);
                    $total = $isAbsent ? 0 : (($theory ?? 0) + ($practical ?? 0));

                    $existing->update([
                        'theory_marks' => $theory,
                        'practical_marks' => $practical,
                        'total_marks' => $total,
                        'is_absent' => $isAbsent,
                        'updated_by' => $userId,
                    ]);
                } else {
                    // Create new result
                    $isAbsent = $markData['is_absent'] ?? false;
                    $theory = $isAbsent ? 0 : ($markData['theory_marks'] ?? null);
                    $practical = $isAbsent ? 0 : ($markData['practical_marks'] ?? null);
                    $total = $isAbsent ? 0 : (($theory ?? 0) + ($practical ?? 0));

                    HeResult::create([
                        'he_admission_id' => $markData['he_admission_id'],
                        'bs_subject_id' => $markData['bs_subject_id'],
                        'semester_no' => $request->semester_no,
                        'exam_type' => $request->exam_type,
                        'theory_marks' => $theory,
                        'practical_marks' => $practical,
                        'total_marks' => $total,
                        'is_absent' => $isAbsent,
                        'academic_year' => $request->academic_year,
                        'is_locked' => false,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
                $savedCount++;
            }

            DB::commit();

            $message = "Successfully saved {$savedCount} result(s).";
            if ($lockedSkipped > 0) {
                $message .= " {$lockedSkipped} locked result(s) were skipped.";
            }

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error saving results: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lock results (submit - marks become read-only for clerk/teacher)
     */
    public function lockResults(Request $request)
    {
        $request->validate([
            'result_ids' => 'required|array',
            'result_ids.*' => 'exists:he_results,id',
        ]);

        $user = Auth::user();
        $userId = $user->id;

        $lockedCount = 0;
        foreach ($request->result_ids as $resultId) {
            $result = HeResult::find($resultId);
            if ($result && !$result->is_locked) {
                $result->lock($userId);
                $lockedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully locked {$lockedCount} result(s). Only Admin/Principal can edit these now."
        ]);
    }

    /**
     * Edit form for results (Admin/Principal only for locked results)
     */
    public function edit(Request $request)
    {
        $user = Auth::user();

        $result = HeResult::with(['heAdmission.student', 'heAdmission.program', 'bsSubject'])
            ->findOrFail($request->id);

        // Check permission
        if (!$result->canBeEditedBy($user)) {
            return redirect()->route('he.results.index')
                ->with('error', 'You do not have permission to edit this locked result.');
        }

        return view('he.results.edit', compact('result'));
    }

    /**
     * Update the specified result
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:he_results,id',
            'theory_marks' => 'nullable|integer|min:0',
            'practical_marks' => 'nullable|integer|min:0',
            'is_absent' => 'boolean',
        ]);

        $user = Auth::user();
        $result = HeResult::findOrFail($request->id);

        // Check permission
        if (!$result->canBeEditedBy($user)) {
            return back()->with('error', 'You do not have permission to edit this locked result.');
        }

        $isAbsent = $request->is_absent ?? false;
        $theory = $isAbsent ? 0 : $request->theory_marks;
        $practical = $isAbsent ? 0 : $request->practical_marks;
        $total = $isAbsent ? 0 : (($theory ?? 0) + ($practical ?? 0));

        $result->update([
            'theory_marks' => $theory,
            'practical_marks' => $practical,
            'total_marks' => $total,
            'is_absent' => $isAbsent,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('he.results.index')
            ->with('success', 'Result updated successfully.');
    }

    /**
     * Get subjects for a program and semester (AJAX)
     */
    public function getSubjects(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $isAdmin = $user->isAdministrativeStaff();
        $isTeacher = $user->isTeacher();
        $staff = $user->staff;

        $subjectsQuery = Semester::where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->with('bsSubject');

        if ($isTeacher) {
            $programId = $request->program_id;
            $semesterNo = $request->semester_no;
            $subjectsQuery->whereHas('bsSubject.teacherAssignments', function ($query) use ($staff, $programId, $semesterNo) {
                $query->where('staff_id', $staff->id)
                      ->whereHas('heAdmission', function ($q) use ($programId, $semesterNo) {
                          $q->where('program_id', $programId)
                            ->where('current_semester', '>=', $semesterNo);
                      });
            });
        }

        $subjects = $subjectsQuery->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->bs_subject_id,
                    'name' => $semester->bsSubject->name ?? 'Unknown',
                    'code' => $semester->bsSubject->code ?? '',
                    'subject_code' => $semester->bsSubject->code ?? '',
                    'theory_credit_hours' => $semester->bsSubject->theory_credit_hours ?? 0,
                    'practical_credit_hours' => $semester->bsSubject->practical_credit_hours ?? 0,
                ];
            });

        return response()->json($subjects);
    }

    /**
     * View results summary
     */
    public function summary(Request $request)
    {
        $query = HeResult::with(['heAdmission.student', 'heAdmission.program', 'bsSubject'])
            ->whereHas('heAdmission')
            ->select('he_results.*');

        if ($request->filled('program_id')) {
            $query->forProgram($request->program_id);
        }

        if ($request->filled('semester_no')) {
            $query->forSemester($request->semester_no);
        }

        if ($request->filled('academic_year')) {
            $query->forAcademicYear($request->academic_year);
        }

        $results = $query->get();

        // Group by student
        $studentResults = $results->groupBy('he_admission_id');

        $programs = Program::orderBy('name')->get();
        $academicYears = HeAdmission::distinctAdmissionYears();

        return view('he.results.summary', compact('studentResults', 'programs', 'academicYears'));
    }
}
