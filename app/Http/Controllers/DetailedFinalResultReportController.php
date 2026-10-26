<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Result;
use App\Models\Student;
use App\Models\Subject;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DetailedFinalResultExport;

class DetailedFinalResultReportController extends Controller
{
    public function index(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderBy('admission_year', 'desc')
            ->pluck('admission_year');

        $reportData = [];
        $selectedYear = $request->admission_year;
        $withSubjects = $request->has('admission_year') ? $request->has('with_subjects') : true;

        if ($selectedYear) {
            $reportData = $this->generateReportData($selectedYear);

            if ($request->input('export') === 'excel') {
                $filename = 'detailed_final_result_report_' . $selectedYear . '.xlsx';
                return Excel::download(new DetailedFinalResultExport($reportData, $withSubjects), $filename);
            }
        }

        return view('reports.detailed-final-result-report.index', compact('admissionYears', 'reportData', 'selectedYear'));
    }

    private function generateReportData($admissionYear)
    {
        // Fetch all active students for the given admission year
        $students = Student::with([
                'admissions', 
                'father', 
                'compulsorySubjects.subject', 
                'electives.subject'
            ])
            ->whereHas('admissions', function($q) use ($admissionYear) {
                $q->where('admission_year', $admissionYear);
            })
            ->whereNull('deleted_at')
            ->get();

        // Sort by roll number
        $students = $students->sortBy(function($student) {
            $roll = $student->admissions->first()->college_roll_no ?? '0';
            return (int) $roll;
        });

        $studentIds = $students->pluck('id')->toArray();

        // Fetch all Final results for these students
        // Ordered by id desc so that if a student has multiple rows for the exact same subject+class, 
        // we take the latest when aggregating or grouping. But mostly they should have max 2 rows per subject (11th and 12th).
        $allResults = Result::whereIn('student_id', $studentIds)
            ->where('exam_type', 'Final')
            ->orderBy('id', 'desc')
            ->get();

        // Group results by student_id and then by subject_id. 
        // Since there might be parts (class 11, class 12) per subject, we will store a mapped array of their combined values.
        $resultsByStudentAndSubject = [];
        foreach ($allResults as $result) {
            $sId = $result->student_id;
            $subId = $result->subject_id;
            
            if (!isset($resultsByStudentAndSubject[$sId][$subId])) {
                $resultsByStudentAndSubject[$sId][$subId] = collect();
            }
            $resultsByStudentAndSubject[$sId][$subId]->push($result);
        }

        $reportData = [];

        foreach ($students as $student) {
            $admission = $student->admissions->first();
            $rollNo = $admission->college_roll_no ?? 'N/A';
            $biseRoll11 = $admission->bise_roll_11 ?? '-';
            $biseRoll12 = $admission->bise_roll_12 ?? '-';
            $firstName = $student->first_name;
            $lastName = $student->last_name ?? '';
            $fullName = trim("$firstName $lastName");
            $fatherName = $student->father ? $student->father->name : 'N/A';

            $studentSubjectResults = $resultsByStudentAndSubject[$student->id] ?? [];

            $compulsory = $student->compulsorySubjects->map(fn($cs) => $cs->subject)->filter();
            $electives = $student->electives->map(fn($es) => $es->subject)->filter();

            // Find specific compulsory subjects
            $english = $compulsory->first(fn($s) => stripos($s->name, 'English') !== false);
            $urdu = $compulsory->first(fn($s) => stripos($s->name, 'Urdu') !== false || stripos($s->name, 'Alternative') !== false || stripos($s->name, 'Pakistani Culture') !== false);
            
            // Islamiyat / Religious Education (50 marks, Class 11 compulsory pair)
            $islamic = $compulsory->first(fn($s) => 
                stripos($s->name, 'Islam') !== false || 
                stripos($s->name, 'Religious Education') !== false || 
                stripos($s->name, 'Civics') !== false
            );
            
            // Tarjuma-tul-quran / Ethics (100 marks, Class 11 + Class 12 compulsory pair)
            $tarjuma = $compulsory->first(fn($s) => 
                stripos($s->name, 'Quran') !== false || 
                stripos($s->name, 'Translation') !== false || 
                stripos($s->name, 'Ethics') !== false || 
                stripos($s->name, 'Akhlaqiat') !== false
            );
            
            $pakStudies = $compulsory->first(fn($s) => stripos($s->name, 'Pakistan Studies') !== false || stripos($s->name, 'Pak Studies') !== false);

            // Resilient fallbacks in case pivot table lacked a subject but student took exam
            if (!$islamic && !empty($studentSubjectResults)) {
                foreach ($studentSubjectResults as $subId => $results) {
                    $sub = $results->first()?->subject;
                    if ($sub && (stripos($sub->name, 'Islam') !== false || stripos($sub->name, 'Religious Education') !== false)) {
                        $islamic = $sub;
                        break;
                    }
                }
            }
            if (!$tarjuma && !empty($studentSubjectResults)) {
                foreach ($studentSubjectResults as $subId => $results) {
                    $sub = $results->first()?->subject;
                    if ($sub && (stripos($sub->name, 'Quran') !== false || stripos($sub->name, 'Translation') !== false || stripos($sub->name, 'Ethics') !== false)) {
                        $tarjuma = $sub;
                        break;
                    }
                }
            }

            // Collect Electives
            $elective1 = $electives->values()->get(0);
            $elective2 = $electives->values()->get(1);
            $elective3 = $electives->values()->get(2);

            $getRowData = function($subject) use ($studentSubjectResults) {
                if (!$subject) {
                    return ['name' => 'N/A', 'marks' => '-', 'details' => ''];
                }
                
                $resultsForSub = $studentSubjectResults[$subject->id] ?? collect();
                
                if ($resultsForSub->isEmpty()) {
                    return ['name' => $subject->name, 'marks' => '-', 'details' => ''];
                }
                
                // Aggregate marks for 11th and 12th (extract latest for each distinct class_level)
                $p1 = $resultsForSub->where('class_level', '11')->first();
                $p2 = $resultsForSub->where('class_level', '12')->first();
                
                if (!$p1 && !$p2) {
                    return ['name' => $subject->name, 'marks' => '-'];
                }

                $isAbsent = ($p1 && $p1->is_absent && $p2 && $p2->is_absent) || ($p1 && $p1->is_absent && !$p2) || ($p2 && $p2->is_absent && !$p1);
                
                // If the student sat for at least one part of the exam, we sum up their marks.
                $totalMarks = 0;
                $appeared = false;

                if ($p1 && !$p1->is_absent) {
                    $totalMarks += $p1->total_obtained_marks;
                    $appeared = true;
                }
                
                if ($p2 && !$p2->is_absent) {
                    $totalMarks += $p2->total_obtained_marks;
                    $appeared = true;
                }

                $maxMarks = 0;
                $p1Text = '-';
                $p2Text = '-';
                $prText = '0';

                if ($p1) {
                    $maxMarks += $p1->total_marks;
                    $p1Text = $p1->is_absent ? 'Abs' : $p1->total_obtained_marks;
                    if ($p1->obtained_practical !== null) {
                        $prText = $p1->obtained_practical;
                    }
                }
                
                if ($p2) {
                    $maxMarks += $p2->total_marks;
                    $p2Text = $p2->is_absent ? 'Abs' : (int)$p2->obtained_theory;
                    if ($p2->obtained_practical !== null) {
                        $prText = $p2->obtained_practical;
                    }
                }

                if (!$appeared && $isAbsent) {
                    $marks = 'Absent';
                    $details = '';
                } else {
                    $marks = "{$totalMarks}/{$maxMarks}";
                    $details = "I:{$p1Text} II:{$p2Text} Pr:{$prText}";
                }
                
                return [
                    'name' => $subject->name,
                    'marks' => $marks,
                    'details' => $details
                ];
            };

            $reportData[] = [
                'roll_no' => $rollNo,
                'bise_roll_11' => $biseRoll11,
                'bise_roll_12' => $biseRoll12,
                'student_name' => $fullName,
                'father_name' => $fatherName,
                'english' => $getRowData($english),
                'urdu' => $getRowData($urdu),
                'islamic' => $getRowData($islamic),
                'tarjuma' => $getRowData($tarjuma),
                'pak_studies' => $getRowData($pakStudies),
                'elective_1' => $getRowData($elective1),
                'elective_2' => $getRowData($elective2),
                'elective_3' => $getRowData($elective3),
            ];
        }

        return collect($reportData);
    }
}
