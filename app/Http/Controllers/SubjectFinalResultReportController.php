<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Staff;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SubjectFinalResultExport;

class SubjectFinalResultReportController extends Controller
{
    /**
     * Display the Subject Final Result Report.
     */
    public function index(Request $request)
    {
        // Fetch all distinct admission years
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->whereNotNull('admission_year')
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $selectedYear = $request->input('admission_year');
        $subjectInput = $request->input('subject_id', $request->input('subject'));
        $selectedStaffId = $request->input('staff_id');

        $subjects = collect();
        $teachers = collect();
        $selectedSubject = null;
        $reportData = collect();
        $summary = [
            'total_students' => 0,
            'appeared' => 0,
            'passed' => 0,
            'failed' => 0,
            'absent' => 0,
            'no_result' => 0,
            'pass_percentage' => 0,
            'average_marks' => 0,
        ];

        if ($selectedYear) {
            // Get all subjects available for this admission year
            $subjects = $this->fetchSubjectsForYear($selectedYear);

            if ($subjectInput) {
                if (is_numeric($subjectInput)) {
                    $selectedSubject = Subject::find($subjectInput);
                } else {
                    $selectedSubject = Subject::where('name', $subjectInput)->first();
                }

                if ($selectedSubject) {
                    // Get all teachers associated with this subject in this admission year
                    $teachers = Staff::whereHas('studentTeacherSubjects', function($q) use ($selectedYear, $selectedSubject) {
                        $q->where('subject_id', $selectedSubject->id)
                          ->whereHas('student.admissions', fn($sub) => $sub->where('admission_year', $selectedYear));
                    })
                    ->orderBy('first_name')
                    ->get();

                    // Generate report data
                    $reportData = $this->generateReportData($selectedYear, $selectedSubject, $selectedStaffId);

                    // Compute summary statistics
                    $total = $reportData->count();
                    $appeared = $reportData->where('status', '!=', 'Absent')->where('status', '!=', 'No Result')->count();
                    $passed = $reportData->where('status', 'Pass')->count();
                    $failed = $reportData->where('status', 'Fail')->count();
                    $absent = $reportData->where('status', 'Absent')->count();
                    $noResult = $reportData->where('status', 'No Result')->count();

                    $validMarks = $reportData->filter(fn($r) => $r['obtained'] !== null && !$r['is_absent']);
                    $avgMarks = $validMarks->count() > 0 ? round($validMarks->avg('obtained'), 1) : 0;
                    $passPercentage = $appeared > 0 ? round(($passed / $appeared) * 100, 1) : 0;

                    $summary = [
                        'total_students' => $total,
                        'appeared' => $appeared,
                        'passed' => $passed,
                        'failed' => $failed,
                        'absent' => $absent,
                        'no_result' => $noResult,
                        'pass_percentage' => $passPercentage,
                        'average_marks' => $avgMarks,
                    ];

                    // Handle Excel export
                    if ($request->input('export') === 'excel') {
                        $filename = 'subject_final_result_' . $selectedYear . '_' . Str::slug($selectedSubject->name) . '.xlsx';
                        return Excel::download(new SubjectFinalResultExport($reportData, $selectedSubject->name, $selectedYear), $filename);
                    }
                }
            }
        }

        return view('reports.subject-final-result-report.index', compact(
            'admissionYears',
            'subjects',
            'teachers',
            'reportData',
            'selectedYear',
            'selectedSubject',
            'selectedStaffId',
            'summary'
        ));
    }

    /**
     * AJAX endpoint to fetch subjects for a specific admission year.
     */
    public function getSubjects(Request $request)
    {
        $admissionYear = $request->input('admission_year');
        if (!$admissionYear) {
            return response()->json([]);
        }

        $subjects = $this->fetchSubjectsForYear($admissionYear);

        return response()->json($subjects);
    }

    /**
     * Helper to get subjects that students of a given admission year are enrolled in.
     */
    private function fetchSubjectsForYear($admissionYear)
    {
        return Subject::where(function($q) use ($admissionYear) {
            $q->whereHas('studentCompulsorySubjects.student.admissions', fn($sub) => $sub->where('admission_year', $admissionYear))
              ->orWhereHas('studentElectives.student.admissions', fn($sub) => $sub->where('admission_year', $admissionYear))
              ->orWhereHas('studentTeacherSubjects.student.admissions', fn($sub) => $sub->where('admission_year', $admissionYear))
              ->orWhereHas('results', fn($sub) => $sub->where('exam_type', 'Final')->whereHas('student.admissions', fn($adm) => $adm->where('admission_year', $admissionYear)));
        })
        ->orderBy('type')
        ->orderBy('name')
        ->get(['id', 'name', 'code', 'type', 'class_level']);
    }

    /**
     * Generate report data for the given year, subject, and optional staff.
     */
    private function generateReportData($admissionYear, Subject $subject, $staffId = null)
    {
        $subjectId = $subject->id;

        $studentsQuery = Student::with([
                'admissions' => function($q) use ($admissionYear) {
                    $q->where('admission_year', $admissionYear)->whereNull('deleted_at');
                },
                'father',
                'studentTeacherSubjects' => function($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId)
                      ->whereNull('deleted_at')
                      ->with('staff');
                },
                'results' => function($q) use ($subjectId) {
                    $q->where('subject_id', $subjectId)
                      ->where('exam_type', 'Final')
                      ->where('class_level', '12')
                      ->whereNull('deleted_at')
                      ->orderBy('id', 'desc');
                }
            ])
            ->whereHas('admissions', function($q) use ($admissionYear) {
                $q->where('admission_year', $admissionYear);
            })
            ->where(function($query) use ($subjectId) {
                $query->whereHas('compulsorySubjects', fn($q) => $q->where('subject_id', $subjectId))
                      ->orWhereHas('electives', fn($q) => $q->where('subject_id', $subjectId))
                      ->orWhereHas('studentTeacherSubjects', fn($q) => $q->where('subject_id', $subjectId))
                      ->orWhereHas('results', fn($q) => $q->where('subject_id', $subjectId)->where('exam_type', 'Final'));
            })
            ->whereNull('deleted_at');

        if ($staffId) {
            $studentsQuery->whereHas('studentTeacherSubjects', function($q) use ($subjectId, $staffId) {
                $q->where('subject_id', $subjectId)->where('staff_id', $staffId);
            });
        }

        $students = $studentsQuery->get();

        // Sort students numerically by College Roll Number
        $students = $students->sortBy(function($student) {
            $roll = $student->admissions->first()->college_roll_no ?? '0';
            return (int) $roll;
        });

        $reportData = [];

        foreach ($students as $student) {
            $admission = $student->admissions->first();
            $rollNo = $admission->college_roll_no ?? 'N/A';
            $biseRoll11 = $admission->bise_roll_11 ?? '-';
            $biseRoll12 = $admission->bise_roll_12 ?? '-';
            $studentName = trim("{$student->first_name} " . ($student->last_name ?? ''));
            $fatherName = $student->father ? $student->father->name : 'N/A';

            // Identify assigned teacher (prefer class 12, fallback to any)
            $stsList = $student->studentTeacherSubjects->where('subject_id', $subjectId);
            $sts12 = $stsList->firstWhere('class_level', '12');
            $targetSts = $sts12 ?? $stsList->first();
            $teacherName = ($targetSts && $targetSts->staff) ? $targetSts->staff->full_name : 'Not Assigned';

            // Identify 12th BISE Final result
            $result = $student->results->first();
            $marks = '-';
            $details = '';
            $status = 'No Result';
            $isAbsent = false;
            $totalObtained = null;
            $totalMarks = null;
            $percentage = null;

            if ($result) {
                $totalMarks = $result->total_marks ?? 0;
                if ($result->is_absent) {
                    $marks = 'Absent';
                    $status = 'Absent';
                    $isAbsent = true;
                    $totalObtained = 0;
                } else {
                    $totalObtained = ($result->obtained_theory ?? 0) + ($result->obtained_practical ?? 0);
                    $marks = "{$totalObtained}/{$totalMarks}";

                    if ($result->obtained_practical !== null && $result->obtained_practical > 0) {
                        $details = "Th: {$result->obtained_theory} | Pr: {$result->obtained_practical}";
                    }

                    $percentage = $totalMarks > 0 ? round(($totalObtained / $totalMarks) * 100, 1) : 0;
                    $status = ($totalMarks > 0 && ($totalObtained / $totalMarks) >= 0.33) ? 'Pass' : 'Fail';
                }
            }

            $reportData[] = [
                'student_id' => $student->id,
                'roll_no' => $rollNo,
                'bise_roll_11' => $biseRoll11 ?: '-',
                'bise_roll_12' => $biseRoll12 ?: '-',
                'student_name' => $studentName,
                'father_name' => $fatherName,
                'subject_name' => $subject->name,
                'marks' => $marks,
                'details' => $details,
                'teacher_name' => $teacherName,
                'status' => $status,
                'is_absent' => $isAbsent,
                'obtained' => $totalObtained,
                'total_marks' => $totalMarks,
                'percentage' => $percentage,
            ];
        }

        return collect($reportData);
    }
}
