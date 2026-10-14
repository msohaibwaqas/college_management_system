<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admissions;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class CustomStudentReportController extends Controller
{
    public function index(Request $request)
    {
        $admissionYears = Admissions::select('admission_year')->distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        
        $reportData = [];
        $selectedColumns = $request->input('columns', []);
        $selectedYear = $request->admission_year;

        if ($selectedYear && !empty($selectedColumns)) {
            $reportData = $this->generateReportData($selectedYear, $selectedColumns, $request->pass_percentage ?? 33);
        }

        return view('reports.custom-student-report.index', compact('admissionYears', 'reportData', 'selectedYear', 'selectedColumns'));
    }

    private function generateReportData($admissionYear, $columns, $passThreshold)
    {
        // 1. Batch Load Admissions (exclude soft-deleted students)
        $admissions = Admissions::where('admission_year', $admissionYear)
            ->whereHas('student')
            ->with(['student.father', 'student.contact', 'student.attribute.bloodGroup', 'student.sscRecord'])
            ->get();
        
        $studentIds = $admissions->pluck('student_id')->toArray();
        if (empty($studentIds)) return [];

        // 2. Batch Load Results if needed
        $needsResults = collect($columns)->contains(function($c) {
            return str_contains($c, 'test') || str_contains($c, 'total') || str_contains($c, 'status') || str_contains($c, 'reappear');
        });

        $allResults = collect();
        if ($needsResults) {
            $allResults = Result::whereIn('student_id', $studentIds)
                ->with('subject')
                ->get()
                ->groupBy('student_id');
        }

        // 3. Batch Load Electives if needed
        $needsElectives = in_array('electives', $columns);
        $studentElectives = collect();
        if ($needsElectives) {
            $studentElectives = DB::table('student_elective_subjects')
                ->join('subjects', 'student_elective_subjects.subject_id', '=', 'subjects.id')
                ->whereIn('student_elective_subjects.student_id', $studentIds)
                ->select('student_elective_subjects.student_id', 'subjects.name')
                ->get()
                ->groupBy('student_id');
        }

        // 4. Pre-calculate Grace/Fail status if needed
        $graceStatusMap = [];
        if ($needsResults) {
            $graceStatusMap = $this->calculateGraceStatusMap($studentIds, $allResults, $passThreshold);
        }

        $data = [];
        foreach ($admissions as $adm) {
            $student = $adm->student;
            if (!$student) continue;

            $row = [];
            
            // Personal
            if (in_array('name', $columns)) $row['Name'] = $student->full_name;
            if (in_array('college_roll_no', $columns)) $row['College Roll No'] = $adm->college_roll_no;
            if (in_array('father_name', $columns)) $row['Father Name'] = $student->father ? $student->father->name : '--';
            if (in_array('religion', $columns)) $row['Religion'] = $student->attribute ? $student->attribute->religion : '--';
            if (in_array('contact', $columns)) $row['Contact'] = $student->contact ? $student->contact->student_contact : '--';
            
            // Board
            if (in_array('bise_roll_11', $columns)) $row['BISE 11 Roll No'] = $adm->bise_roll_11 ?? '--';
            if (in_array('bise_roll_12', $columns)) $row['BISE 12 Roll No'] = $adm->bise_roll_12 ?? '--';
            if (in_array('school_name', $columns)) $row['School Name'] = $student->sscRecord ? $student->sscRecord->school_name : '--';
            if (in_array('ssc_marks', $columns)) $row['SSC Marks'] = $student->sscRecord ? $student->sscRecord->obtained_mark . '/' . $student->sscRecord->total_mark : '--';

            // Academic / Electives
            if ($needsElectives) {
                $electives = $studentElectives->get($student->id, collect());
                $row['Elective Subjects'] = $electives->pluck('name')->implode(', ') ?: '--';
            }

            // Results / Tests
            $sResults = $allResults->get($student->id, collect());
            
            if (in_array('oct_test', $columns)) {
                $oct = $sResults->where('exam_type', 'Oct_test');
                $row['Oct Test'] = $oct->isEmpty() ? '--' : $oct->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            }
            if (in_array('dec_test', $columns)) {
                $dec = $sResults->where('exam_type', 'Dec_test');
                $row['Dec Test'] = $dec->isEmpty() ? '--' : $dec->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            }
            
            // Final Statistics
            if (in_array('total_11', $columns)) {
                $row['11th Total'] = $sResults->where('exam_type', 'Final')->where('class_level', '11')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            }
            if (in_array('total_12', $columns)) {
                $row['12th Total'] = $sResults->where('exam_type', 'Final')->where('class_level', '12')->sum(fn($r) => ($r->obtained_theory ?? 0) + ($r->obtained_practical ?? 0));
            }

            if (in_array('status', $columns) || in_array('reappear', $columns)) {
                $statusData = $this->calculateStudentStats($student->id, $sResults, $graceStatusMap[$student->id] ?? false, $passThreshold);
                if (in_array('status', $columns)) $row['Status'] = $statusData['status'];
                if (in_array('reappear', $columns)) $row['Reappear In'] = $statusData['reappear'];
            }

            $data[] = $row;
        }

        return $data;
    }

    private function calculateGraceStatusMap($studentIds, $allResultsGrouped, $passThreshold)
    {
        $map = [];
        foreach ($studentIds as $id) {
            $results = $allResultsGrouped->get($id, collect())->where('exam_type', 'Final');
            if ($results->isEmpty()) { $map[$id] = false; continue; }

            $grouped = $results->groupBy(fn($item) => $item->subject ? $item->subject->name : 'Unknown');
            $totalMarks = 0; $totalDeficit = 0;

            foreach ($grouped as $g) {
                $sP1 = $g->where('class_level', '11')->first();
                $sP2 = $g->where('class_level', '12')->first();
                $sub = $sP1 ? $sP1->subject : ($sP2 ? $sP2->subject : null);
                
                $max1 = ($sub && $sub->total_11 > 0) ? $sub->total_11 : ($sP1 ? $sP1->total_marks : 0);
                $max2Th = ($sub && $sub->theory_12 > 0) ? $sub->theory_12 : ($sP2 ? $sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0) : 0);
                $maxPrac = ($sub && $sub->practical_12 > 0) ? $sub->practical_12 : 0;
                $subMax = $max1 + $max2Th + $maxPrac;
                $totalMarks += $subMax;

                $obt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0) + ($sP1 ? $sP1->obtained_practical : 0) + ($sP2 ? $sP2->obtained_practical : 0);
                $thObt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0);
                
                $aggPass = round($subMax * $passThreshold / 100);
                $thPass = round(($max1 + $max2Th) * $passThreshold / 100);

                if ($thObt < $thPass || $obt < $aggPass) {
                    $totalDeficit += max(0, $thPass - $thObt, $aggPass - $obt);
                }
            }
            $limit = ceil($totalMarks * 0.01);
            $map[$id] = ($totalDeficit > 0 && $totalDeficit <= $limit);
        }
        return $map;
    }

    private function calculateStudentStats($id, $results, $canUseGrace, $passThreshold)
    {
        $finalResults = $results->where('exam_type', 'Final');
        if ($finalResults->isEmpty()) return ['status' => 'Missing', 'reappear' => '--'];

        $anyAbsent = $finalResults->contains('is_absent', true);
        if ($anyAbsent) return ['status' => 'Absent', 'reappear' => 'Absent'];

        $reappears = [];
        $isOverallPass = true;

        $grouped = $finalResults->groupBy(fn($item) => $item->subject ? $item->subject->name : 'Unknown');
        foreach ($grouped as $name => $g) {
            $sP1 = $g->where('class_level', '11')->first();
            $sP2 = $g->where('class_level', '12')->first();
            $sub = $sP1 ? $sP1->subject : ($sP2 ? $sP2->subject : null);

            $max1 = ($sub && $sub->total_11 > 0) ? $sub->total_11 : ($sP1 ? $sP1->total_marks : 0);
            $max2Th = ($sub && $sub->theory_12 > 0) ? $sub->theory_12 : ($sP2 ? $sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0) : 0);
            $subMax = $max1 + $max2Th + ($sub && $sub->practical_12 > 0 ? $sub->practical_12 : 0);

            $thObt = ($sP1 ? $sP1->obtained_theory : 0) + ($sP2 ? $sP2->obtained_theory : 0);
            $totObt = $thObt + ($sP1 ? $sP1->obtained_practical : 0) + ($sP2 ? $sP2->obtained_practical : 0);

            $aggPass = round($subMax * $passThreshold / 100);
            $thPass = round(($max1 + $max2Th) * $passThreshold / 100);

            if ($thObt < $thPass || $totObt < $aggPass) {
                if (!$canUseGrace) {
                    $isOverallPass = false;
                    $reappears[] = $name;
                }
            }
        }

        if ($isOverallPass) {
            return ['status' => $canUseGrace ? 'Pass (Grace)' : 'Pass', 'reappear' => '--'];
        }
        return ['status' => 'Reappear', 'reappear' => implode(', ', array_unique($reappears))];
    }
}
