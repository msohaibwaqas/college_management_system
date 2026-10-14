<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DetailedTestResultExport;

class DetailedTestResultReportController extends Controller
{
    public function index(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $examTypes = Result::select('exam_type')
            ->distinct()
            ->orderBy('exam_type')
            ->pluck('exam_type');

        $reportData = [];
        $selectedYear = $request->admission_year;
        $selectedExamType = $request->exam_type;
        $selectedClassLevel = $request->class_level; 
        $passPercentage = $request->input('pass_percentage', 40);
        $withSubjects = $request->has('admission_year') ? $request->has('with_subjects') : true;

        if ($selectedYear && $selectedExamType && $selectedClassLevel) {
            $reportData = $this->generateReportData($selectedYear, $selectedExamType, $selectedClassLevel, $passPercentage);

            if ($request->input('export') === 'excel') {
                $filename = 'detailed_test_result_report_' . $selectedYear . '_' . $selectedExamType . '.xlsx';
                return Excel::download(new DetailedTestResultExport($reportData, $withSubjects), $filename);
            }
        }

        return view('reports.detailed-test-result-report.index', compact('admissionYears', 'examTypes', 'reportData', 'selectedYear', 'selectedExamType', 'selectedClassLevel', 'passPercentage'));
    }

    private function generateReportData($admissionYear, $examType, $classLevel, $passPercentage)
    {
        // Fetch all active students for the given admission year and class level
        $students = Student::with([
                'admissions', 
                'father', 
                'compulsorySubjects.subject', 
                'electives.subject'
            ])
            ->whereHas('admissions', function($q) use ($admissionYear, $classLevel) {
                $q->where('admission_year', $admissionYear)
                  ->where('current_class', $classLevel);
            })
            ->whereNull('deleted_at')
            ->get();

        // Sort by roll number
        $students = $students->sortBy(function($student) {
            $roll = $student->admissions->first()->college_roll_no ?? '0';
            return (int) $roll;
        });

        $studentIds = $students->pluck('id')->toArray();

        // Fetch all results
        $results = Result::whereIn('student_id', $studentIds)
            ->where('exam_type', $examType)
            ->where('academic_year', $admissionYear)
            ->where('class_level', $classLevel)
            ->orderBy('id', 'asc')
            ->get();

        $resultsByStudent = [];
        foreach ($results as $result) {
            $resultsByStudent[$result->student_id][$result->subject_id] = $result;
        }

        $reportData = [];

        foreach ($students as $student) {
            $rollNo = $student->admissions->first()->college_roll_no ?? 'N/A';
            $firstName = $student->first_name;
            $lastName = $student->last_name ?? '';
            $fullName = trim("$firstName $lastName");
            $fatherName = $student->father ? $student->father->name : 'N/A';

            $studentResults = $resultsByStudent[$student->id] ?? [];
            
            $compulsory = $student->compulsorySubjects->map(fn($cs) => $cs->subject)->filter();
            $electives = $student->electives->map(fn($es) => $es->subject)->filter();

            $english = $compulsory->first(fn($s) => stripos($s->name, 'English') !== false);
            $urdu = $compulsory->first(fn($s) => stripos($s->name, 'Urdu') !== false || stripos($s->name, 'Alternative') !== false || stripos($s->name, 'Pakistani Culture') !== false);
            $islamic = $compulsory->first(fn($s) => stripos($s->name, 'Islamic') !== false || stripos($s->name, 'Ethics') !== false || stripos($s->name, 'Civics') !== false);
            $tarjuma = $compulsory->first(fn($s) => stripos($s->name, 'Translation') !== false || stripos($s->name, 'Quran') !== false);
            $pakStudies = $compulsory->first(fn($s) => stripos($s->name, 'Pakistan Studies') !== false);

            $elective1 = $electives->values()->get(0);
            $elective2 = $electives->values()->get(1);
            $elective3 = $electives->values()->get(2);

            $passedSubjects = [];
            $failedSubjects = [];
            $absentSubjects = [];

            $getRowData = function($subject) use ($studentResults, &$passedSubjects, &$failedSubjects, &$absentSubjects, $passPercentage, $classLevel) {
                if (!$subject) {
                    return ['name' => 'N/A', 'marks' => '-'];
                }
                
                $result = $studentResults[$subject->id] ?? null;
                if (!$result) return ['name' => $subject->name, 'marks' => '-'];
                
                if ($result->is_absent) {
                    $absentSubjects[] = $subject->name;
                    return ['name' => $subject->name, 'marks' => 'Absent'];
                }

                $marks = $result->total_obtained_marks;
                $totalPossible = $result->total_marks;
                if (!$totalPossible) {
                    $totalPossible = ($classLevel == '11') ? $subject->total_11 : ($subject->theory_12 + $subject->practical_12);
                }

                if ($totalPossible > 0) {
                    $percentage = ($marks / $totalPossible) * 100;
                    if ($percentage >= $passPercentage) {
                        $passedSubjects[] = $subject->name;
                    } else {
                        $failedSubjects[] = $subject->name;
                    }
                }
                
                return ['name' => $subject->name, 'marks' => $marks];
            };

            $englishData = $getRowData($english);
            $urduData = $getRowData($urdu);
            $islamicData = $getRowData($islamic);
            $tarjumaData = $getRowData($tarjuma);
            $pakStudiesData = $getRowData($pakStudies);
            $e1Data = $getRowData($elective1);
            $e2Data = $getRowData($elective2);
            $e3Data = $getRowData($elective3);

            $formatSummary = function($list) {
                return count($list);
            };

            $reportData[] = [
                'roll_no' => $rollNo,
                'student_name' => $fullName,
                'father_name' => $fatherName,
                'english' => $englishData,
                'urdu' => $urduData,
                'islamic' => $islamicData,
                'tarjuma' => $tarjumaData,
                'pak_studies' => $pakStudiesData,
                'elective_1' => $e1Data,
                'elective_2' => $e2Data,
                'elective_3' => $e3Data,
                'pass_summary' => $formatSummary($passedSubjects),
                'fail_summary' => $formatSummary($failedSubjects),
                'absent_summary' => $formatSummary($absentSubjects),
            ];
        }

        return collect($reportData);
    }
}
