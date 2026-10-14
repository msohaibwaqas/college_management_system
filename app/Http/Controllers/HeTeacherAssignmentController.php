<?php

namespace App\Http\Controllers;

use App\Models\HeTeacherAssignment;
use App\Models\HeAdmission;
use App\Models\BsSubject;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\StaffSubjectSpecialization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeTeacherAssignmentController extends Controller
{
    /**
     * Display assignment interface
     */
    public function index()
    {
        $programs = Program::orderBy('name')->get();
        $academicYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        return view('he.teacher_assignment.index', compact('programs', 'academicYears'));
    }

    /**
     * Get subjects for a program and semester (AJAX)
     */
    public function getSubjects(Request $request)
    {
        $programId = $request->program_id;
        $semesterNo = $request->semester_no;

        if (!$programId || !$semesterNo) {
            return response()->json([]);
        }

        $subjects = Semester::where('program_id', $programId)
            ->where('semester_no', $semesterNo)
            ->with('bsSubject')
            ->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->bs_subject_id,
                    'name' => $semester->bsSubject->name ?? 'Unknown',
                    'code' => $semester->bsSubject->code ?? '',
                ];
            });

        return response()->json($subjects);
    }

    /**
     * Get teachers who can teach a subject (AJAX)
     * Only shows active staff with the subject in their BS subject specializations
     */
    public function getTeachers(Request $request)
    {
        $subjectId = $request->subject_id;

        if (!$subjectId) {
            return response()->json([]);
        }

        // Get active teaching staff who have this BS subject in their specializations
        $teachers = Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->whereHas('bsSubjects', function ($query) use ($subjectId) {
                $query->where('bs_subjects.id', $subjectId);
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($teachers);
    }

    /**
     * Get unassigned students for a subject (AJAX)
     * Only shows students whose current_semester equals the selected semester (latest/active students)
     */
    public function getUnassignedStudents(Request $request)
    {
        $programId = $request->program_id;
        $semesterNo = $request->semester_no;
        $subjectId = $request->subject_id;
        $academicYear = $request->academic_year;

        if (!$programId || !$semesterNo || !$subjectId || !$academicYear) {
            return response()->json(['html' => '<div class="p-3 text-muted">Please select all filters</div>']);
        }

        // Get students who are already assigned to any teacher for this subject
        $assignedStudentIds = HeTeacherAssignment::where('bs_subject_id', $subjectId)
            ->pluck('he_admission_id');

        // Get students whose current_semester equals the selected semester (not >=)
        // This ensures we only get students currently in this semester, not previous years
        $students = HeAdmission::with('student')
            ->where('program_id', $programId)
            ->where('current_semester', $semesterNo) // Exact match - only current semester students
            ->where('admission_year', $academicYear)
            ->whereIn('status', ['Active', 'Admitted'])
            ->whereNotIn('id', $assignedStudentIds)
            ->orderBy('roll_no')
            ->get();

        $html = view('he.teacher_assignment._students_list', [
            'students' => $students,
            'type' => 'unassigned'
        ])->render();

        return response()->json(['html' => $html, 'count' => $students->count()]);
    }

    /**
     * Get students assigned to a specific teacher for a subject (AJAX)
     */
    public function getAssignedStudents(Request $request)
    {
        $subjectId = $request->subject_id;
        $staffId = $request->staff_id;
        $academicYear = $request->academic_year;

        if (!$subjectId || !$staffId) {
            return response()->json(['html' => '<div class="p-3 text-muted">Please select all filters</div>']);
        }

        $assignments = HeTeacherAssignment::with(['heAdmission.student'])
            ->where('bs_subject_id', $subjectId)
            ->where('staff_id', $staffId)
            ->when($academicYear, function ($query) use ($academicYear) {
                $query->where('academic_year', $academicYear);
            })
            ->get();

        $students = $assignments->map(function ($assignment) {
            $admission = $assignment->heAdmission;
            return (object)[
                'id' => $admission->id,
                'roll_no' => $admission->roll_no,
                'student_name' => $admission->student 
                    ? trim($admission->student->first_name . ' ' . ($admission->student->last_name ?? ''))
                    : 'Unknown',
                'program_name' => $admission->program->name ?? 'Unknown',
            ];
        });

        $html = view('he.teacher_assignment._students_list', [
            'students' => $students,
            'type' => 'assigned'
        ])->render();

        return response()->json(['html' => $html, 'count' => $students->count()]);
    }

    /**
     * Assign students to a teacher (POST)
     */
    public function assignStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:he_admissions,id',
            'subject_id' => 'required|exists:bs_subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'academic_year' => 'required|integer',
        ]);

        $userId = Auth::id();
        $assignedCount = 0;

        foreach ($request->student_ids as $heAdmissionId) {
            // Check if already assigned
            $existing = HeTeacherAssignment::where('he_admission_id', $heAdmissionId)
                ->where('bs_subject_id', $request->subject_id)
                ->first();

            if (!$existing) {
                HeTeacherAssignment::create([
                    'he_admission_id' => $heAdmissionId,
                    'bs_subject_id' => $request->subject_id,
                    'staff_id' => $request->staff_id,
                    'academic_year' => $request->academic_year,
                    'created_by' => $userId,
                ]);
                $assignedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully assigned {$assignedCount} student(s)."
        ]);
    }

    /**
     * Unassign students from a teacher (POST)
     */
    public function unassignStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:he_admissions,id',
            'subject_id' => 'required|exists:bs_subjects,id',
            'staff_id' => 'required|exists:staff,id',
        ]);

        $assignments = HeTeacherAssignment::whereIn('he_admission_id', $request->student_ids)
            ->where('bs_subject_id', $request->subject_id)
            ->where('staff_id', $request->staff_id)
            ->get();
        
        $deletedCount = 0;
        foreach ($assignments as $assignment) {
            $assignment->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully unassigned {$deletedCount} student(s)."
        ]);
    }
}
