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

                    $validMarks = $reportData->filter(fn($r) => $r['combined_obtained'] !== null && !$r['is_absent']);
                    $avgMarks = $validMarks->count() > 0 ? round($validMarks->avg('combined_obtained'), 1) : 0;
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
                      ->whereIn('class_level', ['11', '12'])
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

            // Identify assigned teacher (prefer class 12, fallback to class 11 or any)
            $stsList = $student->studentTeacherSubjects->where('subject_id', $subjectId);
            $sts12 = $stsList->firstWhere('class_level', '12');
            $targetSts = $sts12 ?? $stsList->first();
            $teacherName = ($targetSts && $targetSts->staff) ? $targetSts->staff->full_name : 'Not Assigned';

            // Extract 11th and 12th Final results
            $res11 = $student->results->where('class_level', '11')->first();
            $res12 = $student->results->where('class_level', '12')->first();

            // 11th marks processing
            $marks11 = '-';
            $obtained11 = null;
            $total11 = null;
            $isAbsent11 = false;
            if ($res11) {
                $total11 = $res11->total_marks ?? 0;
                if ($res11->is_absent) {
                    $marks11 = 'Absent';
                    $isAbsent11 = true;
                    $obtained11 = 0;
                } else {
                    $obtained11 = ($res11->obtained_theory ?? 0) + ($res11->obtained_practical ?? 0);
                    $marks11 = "{$obtained11}/{$total11}";
                }
            }

            // 12th marks processing
            $marks12 = '-';
            $details12 = '';
            $obtained12 = null;
            $total12 = null;
            $isAbsent12 = false;
            if ($res12) {
                $total12 = $res12->total_marks ?? 0;
                if ($res12->is_absent) {
                    $marks12 = 'Absent';
                    $isAbsent12 = true;
                    $obtained12 = 0;
                } else {
                    $obtained12 = ($res12->obtained_theory ?? 0) + ($res12->obtained_practical ?? 0);
                    $marks12 = "{$obtained12}/{$total12}";
                    if ($res12->obtained_practical !== null && $res12->obtained_practical > 0) {
                        $details12 = "Th: {$res12->obtained_theory} | Pr: {$res12->obtained_practical}";
                    }
                }
            }

            // Combined marks calculation
            $combinedMarksDisplay = '-';
            $combinedObtained = null;
            $combinedTotal = null;
            $status = 'No Result';
            $isAbsent = false;

            if ($res11 || $res12) {
                $combinedObtained = 0;
                $combinedTotal = 0;
                $hasValidMarks = false;

                if ($res11) {
                    $combinedTotal += $total11;
                    if (!$isAbsent11) {
                        $combinedObtained += $obtained11;
                        $hasValidMarks = true;
                    }
                }

                if ($res12) {
                    $combinedTotal += $total12;
                    if (!$isAbsent12) {
                        $combinedObtained += $obtained12;
                        $hasValidMarks = true;
                    }
                }

                if (($res11 && $isAbsent11 && !$res12) || (!$res11 && $res12 && $isAbsent12) || ($res11 && $isAbsent11 && $res12 && $isAbsent12)) {
                    $isAbsent = true;
                    $status = 'Absent';
                    $combinedMarksDisplay = 'Absent';
                } else {
                    $combinedMarksDisplay = "{$combinedObtained}/{$combinedTotal}";
                    $status = ($combinedTotal > 0 && ($combinedObtained / $combinedTotal) >= 0.33) ? 'Pass' : 'Fail';
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
                
                // 11th class marks
                'marks_11' => $marks11,
                'obtained_11' => $obtained11,
                'total_11' => $total11,
                'is_absent_11' => $isAbsent11,

                // 12th class marks
                'marks_12' => $marks12,
                'details_12' => $details12,
                'obtained_12' => $obtained12,
                'total_12' => $total12,
                'is_absent_12' => $isAbsent12,

                // Combined BISE marks
                'combined_marks' => $combinedMarksDisplay,
                'combined_obtained' => $combinedObtained,
                'combined_total' => $combinedTotal,

                'teacher_name' => $teacherName,
                'status' => $status,
                'is_absent' => $isAbsent,
            ];
        }

        return collect($reportData);
    }
}
