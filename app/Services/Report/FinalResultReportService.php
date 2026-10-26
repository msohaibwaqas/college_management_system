<?php

namespace App\Services\Report;

use App\Models\Admissions;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class FinalResultReportService
{
    /**
     * Generate report data for the final result report.
     *
     * @param string|int $admissionYear
     * @param float|null $passPercentage
     * @param string $selectedPart 'combine', 'part1', 'part2'
     * @return array
     */
    public function generateReportData($admissionYear, $passPercentage = null, string $selectedPart = 'combine'): array
    {
        $passThreshold = is_numeric($passPercentage) ? (float) $passPercentage : 33.0;

        // Normalize selectedPart
        if ($selectedPart === '11') $selectedPart = 'part1';
        if ($selectedPart === '12') $selectedPart = 'part2';
        if (!in_array($selectedPart, ['part1', 'part2', 'combine'])) {
            $selectedPart = 'combine';
        }

        // 1. Pre-fetch ALL students for this admission year (exclude soft-deleted students)
        $admissionQuery = Admissions::where('admission_year', $admissionYear)
            ->whereHas('student');
        $admissions = $admissionQuery->get()->keyBy('student_id');
        $studentIds = $admissions->keys()->toArray();

        if (empty($studentIds)) {
            return [];
        }

        // 2. Pre-fetch ALL 'Final' results for these students
        $allResults = Result::whereIn('student_id', $studentIds)
            ->where('exam_type', 'Final')
            ->orderBy('id', 'desc')
            ->with('subject')
            ->get()
            ->groupBy('student_id');

        // 3. Pre-calculate Grace Status for EVERY student in one pass
        $graceStatusMap = $this->calculateGraceStatusMap($studentIds, $allResults, $passThreshold, $selectedPart);

        // 4. Get Assignments and Subject Totals
        $assignments = DB::table('student_teacher_subjects')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->where('admissions.admission_year', $admissionYear)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->select('student_teacher_subjects.staff_id', 'student_teacher_subjects.subject_id')
            ->distinct()
            ->get();

        // Batch fetch subjects and staff
        $subjectIdsInvolved = $assignments->pluck('subject_id')->unique();
        $staffIdsInvolved = $assignments->pluck('staff_id')->unique();
        $subjects = Subject::whereIn('id', $subjectIdsInvolved)->get()->keyBy('id');
        $staffs = Staff::whereIn('id', $staffIdsInvolved)->get()->keyBy('id');

        // Enrollment counts
        $compTotals = DB::table('student_compulsory_subjects')
            ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_compulsory_subjects.student_id', '=', 'students.id')
            ->where('admissions.admission_year', $admissionYear)
            ->whereNull('student_compulsory_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->select('student_compulsory_subjects.subject_id', DB::raw('count(*) as total'))
            ->groupBy('student_compulsory_subjects.subject_id')
            ->pluck('total', 'subject_id');

        $electiveTotals = DB::table('student_elective_subjects')
            ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_elective_subjects.student_id', '=', 'students.id')
            ->where('admissions.admission_year', $admissionYear)
            ->whereNull('student_elective_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->select('student_elective_subjects.subject_id', DB::raw('count(*) as total'))
            ->groupBy('student_elective_subjects.subject_id')
            ->pluck('total', 'subject_id');

        $studentTeacherLookup = DB::table('student_teacher_subjects')
            ->join('admissions', 'student_teacher_subjects.student_id', '=', 'admissions.student_id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->where('admissions.admission_year', $admissionYear)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereNull('students.deleted_at')
            ->get()
            ->groupBy(function($item) {
                return $item->staff_id . '_' . $item->subject_id;
            });

        $studentsLookup = Student::whereIn('id', $studentIds)->get()->keyBy('id');

        $data = [];
        foreach ($assignments as $assignment) {
            $staff = $staffs->get($assignment->staff_id);
            $subject = $subjects->get($assignment->subject_id);
            if (!$staff || !$subject) continue;

            // Filter out subjects that do not apply to the selected part
            if ($selectedPart === 'part1' && $subject->total_11 <= 0) {
                continue;
            }
            if ($selectedPart === 'part2' && $subject->theory_12 <= 0 && $subject->practical_12 <= 0) {
                continue;
            }

            $enrolledCount = ($subject->type === 'Compulsory') 
                ? ($compTotals[$subject->id] ?? 0) 
                : ($electiveTotals[$subject->id] ?? 0);

            $key = $staff->id . '_' . $subject->id;
            $assignedStudents = $studentTeacherLookup->get($key, collect());
            $assignedStudentIds = $assignedStudents->pluck('student_id')->toArray();
            $assignedCount = count($assignedStudentIds);

            if ($assignedCount == 0) continue;

            $pass = 0; $fail = 0; $absent = 0; $missing = 0; $missingDetails = [];

            foreach ($assignedStudentIds as $studentId) {
                $studentResults = $allResults->get($studentId, collect())->where('subject_id', $subject->id);
                
                if ($studentResults->isEmpty()) {
                    $missing++;
                    $s = $studentsLookup->get($studentId);
                    if ($s) {
                        $adm = $admissions->get($studentId);
                        $rollNo = ($adm && !empty($adm->college_roll_no)) ? 'Roll: ' . $adm->college_roll_no : 'ID: ' . $studentId;
                        $missingDetails[] = $rollNo . ' (' . trim($s->first_name . ' ' . ($s->last_name ?? '')) . ')';
                    }
                    continue;
                }

                $canUseGrace = $graceStatusMap[$studentId] ?? false;

                if ($selectedPart === 'part1') {
                    $part1 = $studentResults->where('class_level', '11')->first();
                    if (!$part1 || $part1->is_absent) {
                        $absent++;
                    } else {
                        $p1Obt = ($part1->obtained_theory ?? 0) + ($part1->obtained_practical ?? 0);
                        $p1Max = $subject->total_11 > 0 ? $subject->total_11 : ($part1->total_marks > 0 ? $part1->total_marks : 0);
                        $aggThreshold = round($p1Max * $passThreshold / 100);
                        
                        $isPassed = ($p1Obt >= $aggThreshold);
                        if ($isPassed || $canUseGrace) {
                            $pass++;
                        } else {
                            $fail++;
                        }
                    }
                } elseif ($selectedPart === 'part2') {
                    $part2 = $studentResults->where('class_level', '12')->first();
                    if (!$part2 || $part2->is_absent) {
                        $absent++;
                    } else {
                        $p2Obt = ($part2->obtained_theory ?? 0) + ($part2->obtained_practical ?? 0);
                        $p2ThMax = $subject->theory_12 > 0 ? $subject->theory_12 : ($part2->total_marks - ($part2->obtained_practical ? $part2->total_marks * 0.15 : 0));
                        $pPracMax = $subject->practical_12 > 0 ? $subject->practical_12 : 0;
                        if ($subject->theory_12 <= 0 && $subject->practical_12 <= 0) {
                            $p2ThMax = $part2->total_marks;
                        }
                        
                        $subMax = $p2ThMax + $pPracMax;
                        $aggThreshold = round($subMax * $passThreshold / 100);
                        $thThreshold = round($p2ThMax * $passThreshold / 100);
                        $p2Theory = $part2->obtained_theory ?? 0;
                        
                        $isPassed = ($p2Theory >= $thThreshold && $p2Obt >= $aggThreshold);
                        if ($isPassed || $canUseGrace) {
                            $pass++;
                        } else {
                            $fail++;
                        }
                    }
                } else {
                    // Combine (both Part I & II) - exact legacy formula
                    $part1 = $studentResults->where('class_level', '11')->first();
                    $part2 = $studentResults->where('class_level', '12')->first();
                    
                    $anyAbsent = ($part1 && $part1->is_absent) || ($part2 && $part2->is_absent);

                    if ((!$part1 && !$part2) || $anyAbsent) {
                        $absent++;
                    } else {
                        $p1Obt = $part1 ? ($part1->obtained_theory ?? 0) : 0;
                        $p2Obt = $part2 ? ($part2->obtained_theory ?? 0) : 0;
                        $pracObt = ($part1 ? ($part1->obtained_practical ?? 0) : 0) + ($part2 ? ($part2->obtained_practical ?? 0) : 0);
                        $totalObt = $p1Obt + $p2Obt + $pracObt;

                        $p1Max = $subject->total_11 > 0 ? $subject->total_11 : ($part1 ? $part1->total_marks : 0);
                        $p2ThMax = $subject->theory_12 > 0 ? $subject->theory_12 : ($part2 ? $part2->total_marks - ($part2->obtained_practical ? $part2->total_marks * 0.15 : 0) : 0);
                        $pPracMax = $subject->practical_12 > 0 ? $subject->practical_12 : 0;
                        
                        $subMax = $p1Max + $p2ThMax + $pPracMax;
                        $aggThreshold = round($subMax * $passThreshold / 100);
                        $thThreshold = round(($p1Max + $p2ThMax) * $passThreshold / 100);
                        
                        $isPassed = (($p1Obt + $p2Obt) >= $thThreshold && $totalObt >= $aggThreshold);
                        
                        if ($isPassed || $canUseGrace) {
                            $pass++;
                        } else {
                            $fail++;
                        }
                    }
                }
            }

            $appeared = $pass + $fail;
            $data[] = [
                'teacher_name' => $staff->full_name,
                'subject_name' => $subject->name,
                'year' => $admissionYear,
                'part' => $selectedPart,
                'total_enrolled' => $enrolledCount,
                'total_assigned' => $assignedCount,
                'pass' => $pass,
                'fail' => $fail,
                'absent' => $absent,
                'missing' => $missing,
                'missing_details' => $missingDetails,
                'pass_percentage' => $appeared > 0 ? round(($pass / $appeared) * 100, 2) : 0,
            ];
        }

        $groupedData = [];
        foreach ($data as $row) { 
            $groupedData[$row['subject_name']][] = $row; 
        }
        ksort($groupedData);

        foreach ($groupedData as &$rows) {
            usort($rows, function($a, $b) { 
                return strcmp($a['teacher_name'], $b['teacher_name']); 
            });
        }
        return $groupedData;
    }

    /**
     * Pre-calculate Grace Status for EVERY student in one pass based on the selected part.
     */
    private function calculateGraceStatusMap(array $studentIds, $allResults, float $passThreshold, string $selectedPart): array
    {
        $graceStatusMap = [];
        foreach ($studentIds as $sId) {
            $sResults = $allResults->get($sId, collect());
            if ($sResults->isEmpty()) {
                $graceStatusMap[$sId] = false;
                continue;
            }

            $sGrouped = $sResults->groupBy(function($item) {
                return $item->subject ? $item->subject->name : 'Unknown';
            });

            $sTotalMarks = 0;
            $sTotalDeficit = 0;

            foreach ($sGrouped as $sGroup) {
                if ($selectedPart === 'part1') {
                    $sP1 = $sGroup->where('class_level', '11')->first();
                    if (!$sP1 || $sP1->is_absent) continue;

                    $sSub = $sP1->subject;
                    $sSubMax = ($sSub && $sSub->total_11 > 0) ? $sSub->total_11 : ($sP1->total_marks > 0 ? $sP1->total_marks : 0);
                    if ($sSubMax <= 0) continue;

                    $sTotalMarks += $sSubMax;
                    $sObtained = ($sP1->obtained_theory ?? 0) + ($sP1->obtained_practical ?? 0);
                    $sPassThreshold = round($sSubMax * $passThreshold / 100);
                    if ($sObtained < $sPassThreshold) {
                        $sTotalDeficit += ($sPassThreshold - $sObtained);
                    }
                } elseif ($selectedPart === 'part2') {
                    $sP2 = $sGroup->where('class_level', '12')->first();
                    if (!$sP2 || $sP2->is_absent) continue;

                    $sSub = $sP2->subject;
                    $sp2TheoryMax = ($sSub && $sSub->theory_12 > 0) ? $sSub->theory_12 : ($sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0));
                    $spPracMax = ($sSub && $sSub->practical_12 > 0) ? $sSub->practical_12 : 0;
                    if (!$sSub || ($sSub->theory_12 <= 0 && $sSub->practical_12 <= 0)) {
                        $sp2TheoryMax = $sP2->total_marks;
                    }

                    $sSubMax = $sp2TheoryMax + $spPracMax;
                    if ($sSubMax <= 0) continue;

                    $sTotalMarks += $sSubMax;
                    $sp2Theory = $sP2->obtained_theory ?? 0;
                    $sObtained = $sp2Theory + ($sP2->obtained_practical ?? 0);

                    $sAggPassThreshold = round($sSubMax * $passThreshold / 100);
                    $sTheoryPassThreshold = round($sp2TheoryMax * $passThreshold / 100);

                    $sDeficit = 0;
                    if ($sp2Theory < $sTheoryPassThreshold || $sObtained < $sAggPassThreshold) {
                        $sDeficit = max(0, $sTheoryPassThreshold - $sp2Theory, $sAggPassThreshold - $sObtained);
                    }
                    $sTotalDeficit += $sDeficit;
                } else {
                    // Combine (both Part I & II)
                    $sP1 = $sGroup->where('class_level', '11')->first();
                    $sP2 = $sGroup->where('class_level', '12')->first();
                    $sSub = $sP1 ? $sP1->subject : ($sP2 ? $sP2->subject : null);

                    $sp1Theory = $sP1 ? ($sP1->obtained_theory ?? 0) : 0;
                    $sp2Theory = $sP2 ? ($sP2->obtained_theory ?? 0) : 0;
                    $sTotalPrac = ($sP1 ? ($sP1->obtained_practical ?? 0) : 0) + ($sP2 ? ($sP2->obtained_practical ?? 0) : 0);

                    $sp1Max = ($sSub && $sSub->total_11 > 0) ? $sSub->total_11 : ($sP1 && $sP1->total_marks > 0 ? $sP1->total_marks : 0);
                    $sp2TheoryMax = ($sSub && $sSub->theory_12 > 0) ? $sSub->theory_12 : ($sP2 ? $sP2->total_marks - ($sP2->obtained_practical ? $sP2->total_marks * 0.15 : 0) : 0);
                    $spPracMax = ($sSub && $sSub->practical_12 > 0) ? $sSub->practical_12 : 0;
                    if (!$sSub && $sP2) $sp2TheoryMax = $sP2->total_marks;

                    $sSubMax = $sp1Max + $sp2TheoryMax + $spPracMax;
                    $sTotalMarks += $sSubMax;

                    $sObtained = $sp1Theory + $sp2Theory + $sTotalPrac;
                    $sTheoryObtained = $sp1Theory + $sp2Theory;

                    $sAggPassThreshold = round($sSubMax * $passThreshold / 100);
                    $sTheoryPassThreshold = round(($sp1Max + $sp2TheoryMax) * $passThreshold / 100);

                    $sDeficit = 0;
                    if ($sTheoryObtained < $sTheoryPassThreshold || $sObtained < $sAggPassThreshold) {
                        $sDeficit = max(0, $sTheoryPassThreshold - $sTheoryObtained, $sAggPassThreshold - $sObtained);
                    }
                    $sTotalDeficit += $sDeficit;
                }
            }

            $gracePoolLimit = ceil($sTotalMarks * 0.01);
            $graceStatusMap[$sId] = ($sTotalDeficit > 0 && $sTotalDeficit <= $gracePoolLimit);
        }

        return $graceStatusMap;
    }
}
