<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subject;
use App\Models\Admissions;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SubjectEnrollmentReportController extends Controller
{
    /**
     * Show the subject enrollment report form
     */
    public function index()
    {
        // Get distinct admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        return view('reports.subject-enrollment.index', compact('admissionYears'));
    }

    /**
     * Get subjects for a specific admission year
     */
    public function getSubjects(Request $request)
    {
        $admissionYear = $request->admission_year;

        // Get all subjects (compulsory + elective) that have students from this admission year
        $subjects = Subject::select('subjects.id', 'subjects.name', 'subjects.code', 'subjects.type', 'subjects.class_level')
            ->leftJoin('student_compulsory_subjects', 'subjects.id', '=', 'student_compulsory_subjects.subject_id')
            ->leftJoin('student_elective_subjects', 'subjects.id', '=', 'student_elective_subjects.subject_id')
            ->leftJoin('admissions as adm1', 'student_compulsory_subjects.student_id', '=', 'adm1.student_id')
            ->leftJoin('admissions as adm2', 'student_elective_subjects.student_id', '=', 'adm2.student_id')
            ->where(function($query) use ($admissionYear) {
                $query->where('adm1.admission_year', $admissionYear)
                      ->orWhere('adm2.admission_year', $admissionYear);
            })
            ->groupBy('subjects.id', 'subjects.name', 'subjects.code', 'subjects.type', 'subjects.class_level')
            ->orderBy('subjects.type')
            ->orderBy('subjects.name')
            ->get();

        return response()->json($subjects);
    }

    /**
     * Show enrollment report for a specific subject and admission year
     */
    public function showReport(Request $request)
    {
        $admissionYear = $request->admission_year;
        $subjectId = $request->subject_id;

        if ($subjectId === 'all_electives') {
            $subject = (object)[
                'id' => 'all_electives',
                'name' => 'All Students (Elective Subjects Breakdown)',
                'code' => 'ALL-ELECTIVES',
                'type' => 'Elective List'
            ];

            $students = \App\Models\Student::with(['admissions', 'father', 'electives.subject'])
                ->whereHas('admissions', function($q) use ($admissionYear) {
                    $q->where('admission_year', $admissionYear)
                      ->whereIn('status', ['Active', 'Admitted']);
                })
                ->get()
                ->sortBy(function($student) {
                    $roll = optional($student->admissions->first())->college_roll_no;
                    return is_numeric($roll) ? (int)$roll : 999999;
                })
                ->values();

            return view('reports.subject-enrollment.report', compact('subject', 'admissionYear', 'students'));
        }

        $subject = Subject::findOrFail($subjectId);

        // Get students enrolled in this subject for the given admission year
        $students = $this->getEnrolledStudents($subjectId, $admissionYear, $subject->type);

        return view('reports.subject-enrollment.report', compact('subject', 'admissionYear', 'students'));
    }

    /**
     * Export enrollment report as PDF
     */
    public function exportPdf(Request $request)
    {
        $admissionYear = $request->admission_year;
        $subjectId = $request->subject_id;

        if ($subjectId === 'all_electives') {
            $subject = (object)[
                'id' => 'all_electives',
                'name' => 'All Students (Elective Subjects Breakdown)',
                'code' => 'ALL-ELECTIVES',
                'type' => 'Elective List'
            ];

            $students = \App\Models\Student::with(['admissions', 'father', 'electives.subject'])
                ->whereHas('admissions', function($q) use ($admissionYear) {
                    $q->where('admission_year', $admissionYear)
                      ->whereIn('status', ['Active', 'Admitted']);
                })
                ->get()
                ->sortBy(function($student) {
                    $roll = optional($student->admissions->first())->college_roll_no;
                    return is_numeric($roll) ? (int)$roll : 999999;
                })
                ->values();

            $collegeName = config('app.name', 'Government College');

            $pdf = Pdf::loadView('reports.subject-enrollment.pdf', compact('subject', 'admissionYear', 'students', 'collegeName'))
                ->setPaper('a4', 'portrait');

            return $pdf->download("all-students-elective-subjects-{$admissionYear}.pdf");
        }

        $subject = Subject::findOrFail($subjectId);
        $students = $this->getEnrolledStudents($subjectId, $admissionYear, $subject->type);

        $collegeName = config('app.name', 'Government College');

        $pdf = Pdf::loadView('reports.subject-enrollment.pdf', compact('subject', 'admissionYear', 'students', 'collegeName'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("subject-enrollment-{$subject->code}-{$admissionYear}.pdf");
    }

    /**
     * Show students without teacher form
     */
    public function withoutTeacherIndex()
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        return view('reports.students-without-teacher.index', compact('admissionYears'));
    }

    /**
     * Show students without teacher report
     */
    public function withoutTeacherReport(Request $request)
    {
        $admissionYear = $request->admission_year;
        $subjectId = $request->subject_id;

        $subject = null;
        if (!empty($subjectId) && $subjectId !== 'all') {
            $subject = Subject::find($subjectId);
        }

        $reportData = $this->getAllStudentsWithoutTeacher($admissionYear, $subject ? $subject->id : null);

        return view('reports.students-without-teacher.report', compact('subject', 'admissionYear', 'reportData'));
    }

    /**
     * Export students without teacher as PDF
     */
    public function withoutTeacherPdf(Request $request)
    {
        $admissionYear = $request->admission_year;
        $subjectId = $request->subject_id;

        $subject = null;
        if (!empty($subjectId) && $subjectId !== 'all') {
            $subject = Subject::find($subjectId);
        }

        $reportData = $this->getAllStudentsWithoutTeacher($admissionYear, $subject ? $subject->id : null);

        $collegeName = config('app.name', 'Government College');

        $pdf = Pdf::loadView('reports.students-without-teacher.pdf', compact('subject', 'admissionYear', 'reportData', 'collegeName'))
            ->setPaper('a4', 'portrait');

        $filename = $subject ? "students-without-teacher-{$subject->code}-{$admissionYear}.pdf" : "students-without-teacher-{$admissionYear}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Helper: Get enrolled students
     */
    private function getEnrolledStudents($subjectId, $admissionYear, $subjectType)
    {
        if ($subjectType === 'Compulsory') {
            $students = DB::table('student_compulsory_subjects')
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'admissions.student_id', '=', 'students.id')
                ->where('student_compulsory_subjects.subject_id', $subjectId)
                ->where('admissions.admission_year', $admissionYear)
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->whereNull('students.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->select(
                    'students.id',
                    'students.first_name',
                    'students.last_name',
                    'admissions.college_roll_no',
                    'admissions.current_class'
                )
                ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
                ->get();
        } else {
            $students = DB::table('student_elective_subjects')
                ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
                ->join('students', 'admissions.student_id', '=', 'students.id')
                ->where('student_elective_subjects.subject_id', $subjectId)
                ->where('admissions.admission_year', $admissionYear)
                ->whereIn('admissions.status', ['Active', 'Admitted'])
                ->whereNull('students.deleted_at')
                ->whereNull('admissions.deleted_at')
                ->select(
                    'students.id',
                    'students.first_name',
                    'students.last_name',
                    'admissions.college_roll_no',
                    'admissions.current_class'
                )
                ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED)')
                ->get();
        }

        // Attach elective subjects to each student
        $studentIds = $students->pluck('id')->toArray();
        if (!empty($studentIds)) {
            $electivesMap = DB::table('student_elective_subjects')
                ->join('subjects', 'student_elective_subjects.subject_id', '=', 'subjects.id')
                ->whereIn('student_elective_subjects.student_id', $studentIds)
                ->select('student_elective_subjects.student_id', 'subjects.name as subject_name')
                ->orderBy('student_elective_subjects.sequence')
                ->get()
                ->groupBy('student_id');

            foreach ($students as $student) {
                $sElectives = $electivesMap->get($student->id, collect());
                $student->elective_subjects = $sElectives->pluck('subject_name')->implode(', ');
            }
        }

        return $students;
    }

    /**
     * Helper: Get all students with unassigned subjects for an admission year
     */
    private function getAllStudentsWithoutTeacher($admissionYear, $subjectId = null)
    {
        // 1. Fetch active/admitted students for the given admission year
        $students = \App\Models\Student::with(['admissions', 'father'])
            ->whereHas('admissions', function($q) use ($admissionYear) {
                $q->where('admission_year', $admissionYear)
                  ->whereIn('status', ['Active', 'Admitted']);
            })
            ->get()
            ->keyBy('id');

        $studentIds = $students->keys()->toArray();

        if (empty($studentIds)) {
            return collect();
        }

        // 2. Compulsory subjects for these students
        $compQuery = DB::table('student_compulsory_subjects')
            ->join('subjects', 'student_compulsory_subjects.subject_id', '=', 'subjects.id')
            ->whereIn('student_compulsory_subjects.student_id', $studentIds)
            ->whereNull('student_compulsory_subjects.deleted_at')
            ->whereNull('subjects.deleted_at');

        if ($subjectId) {
            $compQuery->where('student_compulsory_subjects.subject_id', $subjectId);
        }

        $compSubjects = $compQuery->select(
            'student_compulsory_subjects.student_id',
            'subjects.id as subject_id',
            'subjects.name as subject_name'
        )->get();

        // 3. Elective subjects for these students
        $elecQuery = DB::table('student_elective_subjects')
            ->join('subjects', 'student_elective_subjects.subject_id', '=', 'subjects.id')
            ->whereIn('student_elective_subjects.student_id', $studentIds)
            ->whereNull('student_elective_subjects.deleted_at')
            ->whereNull('subjects.deleted_at');

        if ($subjectId) {
            $elecQuery->where('student_elective_subjects.subject_id', $subjectId);
        }

        $elecSubjects = $elecQuery->select(
            'student_elective_subjects.student_id',
            'subjects.id as subject_id',
            'subjects.name as subject_name'
        )->get();

        $allEnrolled = $compSubjects->concat($elecSubjects);

        // 4. Teacher assignments
        $assignedTeacherSubjects = DB::table('student_teacher_subjects')
            ->whereIn('student_id', $studentIds)
            ->whereNull('deleted_at')
            ->select('student_id', 'subject_id')
            ->get()
            ->groupBy(function($item) {
                return $item->student_id . '_' . $item->subject_id;
            });

        // 5. Build report per student
        $reportData = [];

        foreach ($students as $studentId => $student) {
            $studentEnrolled = $allEnrolled->where('student_id', $studentId);
            $unassignedForStudent = [];

            foreach ($studentEnrolled as $enrolled) {
                $key = $studentId . '_' . $enrolled->subject_id;
                if (!$assignedTeacherSubjects->has($key)) {
                    $unassignedForStudent[] = $enrolled->subject_name;
                }
            }

            if (!empty($unassignedForStudent)) {
                $admission = $student->admissions->first();
                $rollNo = $admission ? $admission->college_roll_no : 'N/A';

                $reportData[] = [
                    'student_id' => $studentId,
                    'college_roll_no' => $rollNo,
                    'sort_roll' => is_numeric($rollNo) ? (int)$rollNo : 999999,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'father_name' => optional($student->father)->name ?? 'N/A',
                    'current_class' => $admission ? $admission->current_class : '-',
                    'unassigned_subjects' => array_values(array_unique($unassignedForStudent)),
                    'unassigned_subjects_str' => implode(', ', array_values(array_unique($unassignedForStudent)))
                ];
            }
        }

        usort($reportData, function($a, $b) {
            return $a['sort_roll'] <=> $b['sort_roll'];
        });

        return collect($reportData);
    }
}
