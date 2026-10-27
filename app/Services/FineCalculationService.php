<?php

namespace App\Services;

use App\Models\FineRule;
use App\Models\StudentFine;
use App\Models\Student;
use App\Models\Admissions;
use App\Models\Attendance;
use App\Models\Results;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FineCalculationService
{
    /**
     * Get available calendar months from attendances table.
     */
    public function getAvailableMonths(): Collection
    {
        $currentMonth = now()->format('Y-m');
        $months = DB::table('attendances')
            ->select(DB::raw("DATE_FORMAT(attendance_date, '%Y-%m') as m"))
            ->distinct()
            ->orderByDesc('m')
            ->limit(12)
            ->pluck('m');

        if (!$months->contains($currentMonth)) {
            $months->prepend($currentMonth);
        }

        return $months;
    }

    /**
     * Get available exams with date range and attendance details from datesheets, results, and rules.
     */
    public function getAvailableExamsWithDetails(): Collection
    {
        $details = [];

        // 1. From Datesheets (preset named ranges)
        $datesheetExams = DB::table('datesheets')
            ->select(
                'exam_name',
                DB::raw('MIN(test_date) as start_date'),
                DB::raw('MAX(test_date) as end_date'),
                DB::raw('COUNT(*) as total_tests')
            )
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->groupBy('exam_name')
            ->get();

        foreach ($datesheetExams as $ds) {
            $key = strtolower(trim($ds->exam_name));
            $start = $ds->start_date ? date('d M Y', strtotime($ds->start_date)) : null;
            $end = $ds->end_date ? date('d M Y', strtotime($ds->end_date)) : null;
            $rangeText = ($start && $end) ? ($start === $end ? $start : "$start – $end") : '';

            $details[$key] = [
                'exam_name'      => $ds->exam_name,
                'start_date'     => $ds->start_date,
                'end_date'       => $ds->end_date,
                'range_text'     => $rangeText,
                'source'         => 'datesheet',
                'total_tests'    => (int) $ds->total_tests,
                'total_students' => 0,
                'total_absent'   => 0,
            ];
        }

        // 2. From Results (where test attendance or test marks have been recorded)
        $resultExams = DB::table('results')
            ->select(
                'exam_name',
                DB::raw('MIN(DATE(created_at)) as start_date'),
                DB::raw('MAX(DATE(created_at)) as end_date'),
                DB::raw('COUNT(*) as total_students'),
                DB::raw('SUM(CASE WHEN is_absent = 1 THEN 1 ELSE 0 END) as total_absent')
            )
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->where('exam_name', '!=', 'Final')
            ->groupBy('exam_name')
            ->get();

        foreach ($resultExams as $re) {
            $key = strtolower(trim($re->exam_name));
            if (!isset($details[$key])) {
                $start = $re->start_date ? date('d M Y', strtotime($re->start_date)) : null;
                $end = $re->end_date ? date('d M Y', strtotime($re->end_date)) : null;
                $rangeText = ($start && $end) ? ($start === $end ? $start : "$start – $end") : '';

                $details[$key] = [
                    'exam_name'      => $re->exam_name,
                    'start_date'     => $re->start_date,
                    'end_date'       => $re->end_date,
                    'range_text'     => $rangeText,
                    'source'         => 'results',
                    'total_tests'    => 0,
                    'total_students' => (int) $re->total_students,
                    'total_absent'   => (int) $re->total_absent,
                ];
            } else {
                $details[$key]['total_students'] = (int) $re->total_students;
                $details[$key]['total_absent'] = (int) $re->total_absent;
            }
        }

        // 3. From Fine Rules
        $ruleExams = DB::table('fine_rules')
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->distinct()
            ->pluck('exam_name');

        foreach ($ruleExams as $rName) {
            $key = strtolower(trim($rName));
            if (!isset($details[$key])) {
                $details[$key] = [
                    'exam_name'      => $rName,
                    'start_date'     => null,
                    'end_date'       => null,
                    'range_text'     => '',
                    'source'         => 'rule',
                    'total_tests'    => 0,
                    'total_students' => 0,
                    'total_absent'   => 0,
                ];
            }
        }

        return collect(array_values($details))->sortByDesc(function ($item) {
            return $item['end_date'] ?? $item['start_date'] ?? '';
        })->values();
    }

    /**
     * Get available exam names from datesheets, results and fine_rules tables.
     */
    public function getAvailableExamNames(): Collection
    {
        return $this->getAvailableExamsWithDetails()->pluck('exam_name')->values();
    }

    /**
     * Calculate and preview fine items for the given criteria.
     */
    public function preview(array $filters): Collection
    {
        $fineType = $filters['fine_type'] ?? 'lecture_absent';
        $academicYear = isset($filters['academic_year']) && $filters['academic_year'] !== '' ? (int) $filters['academic_year'] : (int) date('Y');
        $classLevel = $filters['class_level'] ?? null; // '11', '12', or null/all
        $examName = $filters['exam_name'] ?? null;
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;
        $period = $filters['period'] ?? now()->format('Y-m'); // e.g. "2026-10"

        // Determine rate and pass percentage
        $rule = null;
        if (!empty($filters['fine_rule_id'])) {
            $rule = FineRule::find($filters['fine_rule_id']);
            if ($rule) {
                $fineType = $filters['fine_type'] ?? $rule->fine_type;
                // Only use rule's exam_name if user didn't specify one
                if (empty($examName) && $rule->exam_name) {
                    $examName = $rule->exam_name;
                }
            }
        }

        $rate = isset($filters['rate']) && $filters['rate'] !== ''
            ? (float) $filters['rate']
            : ($rule ? (float) $rule->amount_per_unit : 100.00);

        $passPercentage = isset($filters['pass_percentage']) && $filters['pass_percentage'] !== ''
            ? (float) $filters['pass_percentage']
            : ($rule ? (float) $rule->pass_percentage : 33.00);

        return match ($fineType) {
            'lecture_absent' => $this->previewLectureAbsentFines($period, $academicYear, $classLevel, $rate, $rule),
            'test_absent'    => $this->previewTestAbsentFines($examName, $academicYear, $classLevel, $rate, $rule, $startDate, $endDate),
            'test_fail'      => $this->previewTestFailFines($examName, $academicYear, $classLevel, $rate, $passPercentage, $rule, $startDate, $endDate),
            default          => collect(),
        };
    }

    /**
     * Preview Lecture Absence Fines.
     */
    protected function previewLectureAbsentFines(string $period, int $academicYear, ?string $classLevel, float $rate, ?FineRule $rule): Collection
    {
        $query = DB::table('attendances')
            ->join('students', 'attendances.student_id', '=', 'students.id')
            ->join('admissions', function ($join) {
                $join->on('students.id', '=', 'admissions.student_id')
                    ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted']);
            })
            ->where('attendances.status', 'absent')
            ->whereRaw("DATE_FORMAT(attendances.attendance_date, '%Y-%m') = ?", [$period]);

        if ($classLevel && in_array($classLevel, ['11', '12'])) {
            $query->where('admissions.current_class', $classLevel);
        }

        $records = $query->select(
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
                'admissions.id as admission_id',
                'admissions.college_roll_no',
                'admissions.current_class as class_level',
                DB::raw('COUNT(attendances.id) as absent_count')
            )
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'admissions.id', 'admissions.college_roll_no', 'admissions.current_class')
            ->havingRaw('COUNT(attendances.id) > 0')
            ->orderBy('admissions.college_roll_no')
            ->get();

        // Check for existing fines in this period
        $existingFines = StudentFine::where('fine_type', 'lecture_absent')
            ->where('academic_year', $academicYear)
            ->where('period', $period)
            ->get()
            ->keyBy('student_id');

        return $records->map(function ($row) use ($rate, $period, $academicYear, $rule, $existingFines) {
            $count = (int) $row->absent_count;
            $totalAmount = round($count * $rate, 2);
            $fullName = trim("{$row->first_name} {$row->last_name}");
            $existing = $existingFines->get($row->student_id);

            return [
                'student_id'       => $row->student_id,
                'student_name'     => $fullName,
                'college_roll_no'  => $row->college_roll_no,
                'class_level'      => $row->class_level,
                'admission_id'     => $row->admission_id,
                'fine_type'        => 'lecture_absent',
                'fine_rule_id'     => $rule?->id,
                'exam_name'        => null,
                'subject_id'       => null,
                'academic_year'    => $academicYear,
                'period'           => $period,
                'units_count'      => $count,
                'amount_per_unit'  => $rate,
                'total_amount'     => $totalAmount,
                'reason'           => "{$count} lecture absence(s) in month {$period} (Rs. " . number_format($rate, 2) . " / absent)",
                'already_exists'   => $existing !== null,
                'existing_fine'    => $existing,
            ];
        });
    }

    /**
     * Preview Test Absence Fines.
     */
    protected function previewTestAbsentFines(?string $examName, int $academicYear, ?string $classLevel, float $rate, ?FineRule $rule, ?string $startDate = null, ?string $endDate = null): Collection
    {
        if (!$examName && !$startDate && !$endDate) {
            return collect();
        }

        $query = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('subjects', 'results.subject_id', '=', 'subjects.id')
            ->join('admissions', function ($join) {
                $join->on('students.id', '=', 'admissions.student_id')
                    ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted']);
            })
            ->where('results.is_absent', 1)
            ->whereNull('results.deleted_at');

        if ($examName) {
            $query->whereRaw('LOWER(TRIM(results.exam_name)) = ?', [strtolower(trim($examName))]);
        }

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(results.created_at)'), [$startDate, $endDate])
                  ->orWhereExists(function ($sub) use ($startDate, $endDate) {
                      $sub->select(DB::raw(1))
                          ->from('datesheets')
                          ->whereColumn('datesheets.subject_id', 'results.subject_id')
                          ->whereBetween('datesheets.test_date', [$startDate, $endDate]);
                  });
            });
        }

        if ($classLevel && in_array($classLevel, ['11', '12'])) {
            $query->where(function ($q) use ($classLevel) {
                $q->where('admissions.current_class', $classLevel)
                  ->orWhere('results.class_level', $classLevel);
            });
        } elseif ($academicYear) {
            $query->where(function ($q) use ($academicYear) {
                $q->where('admissions.academic_year', $academicYear)
                  ->orWhere('results.academic_year', $academicYear)
                  ->orWhere(function ($sub) use ($academicYear) {
                      $sub->where('results.academic_year', $academicYear - 1)
                          ->where(function ($c) {
                              $c->where('admissions.current_class', '12')
                                ->orWhere('results.class_level', '12');
                          });
                  });
            });
        }

        $records = $query->select(
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
                'admissions.id as admission_id',
                'admissions.college_roll_no',
                'admissions.current_class as class_level',
                DB::raw('COUNT(results.id) as absent_papers_count'),
                DB::raw("GROUP_CONCAT(subjects.name SEPARATOR ', ') as missed_subjects")
            )
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'admissions.id', 'admissions.college_roll_no', 'admissions.current_class')
            ->havingRaw('COUNT(results.id) > 0')
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED), admissions.college_roll_no')
            ->get();

        $resolvedExamName = $examName ?: ($startDate && $endDate ? "Exams ($startDate to $endDate)" : 'Test Exam');

        $existingFines = StudentFine::where('fine_type', 'test_absent')
            ->where('academic_year', $academicYear)
            ->where(function ($q) use ($resolvedExamName) {
                $q->where('exam_name', $resolvedExamName)
                  ->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($resolvedExamName))]);
            })
            ->get()
            ->keyBy('student_id');

        return $records->map(function ($row) use ($rate, $resolvedExamName, $academicYear, $rule, $existingFines) {
            $count = (int) $row->absent_papers_count;
            $totalAmount = round($count * $rate, 2);
            $fullName = trim("{$row->first_name} {$row->last_name}");
            $existing = $existingFines->get($row->student_id);

            return [
                'student_id'       => $row->student_id,
                'student_name'     => $fullName,
                'college_roll_no'  => $row->college_roll_no,
                'class_level'      => $row->class_level,
                'admission_id'     => $row->admission_id,
                'fine_type'        => 'test_absent',
                'fine_rule_id'     => $rule?->id,
                'exam_name'        => $resolvedExamName,
                'subject_id'       => null,
                'academic_year'    => $academicYear,
                'period'           => $resolvedExamName,
                'units_count'      => $count,
                'amount_per_unit'  => $rate,
                'total_amount'     => $totalAmount,
                'reason'           => "Absent from {$count} paper(s) in {$resolvedExamName} [{$row->missed_subjects}] (Rs. " . number_format($rate, 2) . " / paper)",
                'already_exists'   => $existing !== null,
                'existing_fine'    => $existing,
            ];
        });
    }

    /**
     * Preview Test Failure Fines.
     */
    protected function previewTestFailFines(?string $examName, int $academicYear, ?string $classLevel, float $rate, float $passPercentage, ?FineRule $rule, ?string $startDate = null, ?string $endDate = null): Collection
    {
        if (!$examName && !$startDate && !$endDate) {
            return collect();
        }

        $query = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('subjects', 'results.subject_id', '=', 'subjects.id')
            ->join('admissions', function ($join) {
                $join->on('students.id', '=', 'admissions.student_id')
                    ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted']);
            })
            ->where('results.is_absent', 0)
            ->whereNotNull('results.obtained_theory')
            ->where('results.total_marks', '>', 0)
            ->whereRaw("((COALESCE(results.obtained_theory, 0) + COALESCE(results.obtained_practical, 0)) / results.total_marks) * 100 < ?", [$passPercentage])
            ->whereNull('results.deleted_at');

        if ($examName) {
            $query->whereRaw('LOWER(TRIM(results.exam_name)) = ?', [strtolower(trim($examName))]);
        }

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(DB::raw('DATE(results.created_at)'), [$startDate, $endDate])
                  ->orWhereExists(function ($sub) use ($startDate, $endDate) {
                      $sub->select(DB::raw(1))
                          ->from('datesheets')
                          ->whereColumn('datesheets.subject_id', 'results.subject_id')
                          ->whereBetween('datesheets.test_date', [$startDate, $endDate]);
                  });
            });
        }

        if ($classLevel && in_array($classLevel, ['11', '12'])) {
            $query->where(function ($q) use ($classLevel) {
                $q->where('admissions.current_class', $classLevel)
                  ->orWhere('results.class_level', $classLevel);
            });
        } elseif ($academicYear) {
            $query->where(function ($q) use ($academicYear) {
                $q->where('admissions.academic_year', $academicYear)
                  ->orWhere('results.academic_year', $academicYear)
                  ->orWhere(function ($sub) use ($academicYear) {
                      $sub->where('results.academic_year', $academicYear - 1)
                          ->where(function ($c) {
                              $c->where('admissions.current_class', '12')
                                ->orWhere('results.class_level', '12');
                          });
                  });
            });
        }

        $records = $query->select(
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
                'admissions.id as admission_id',
                'admissions.college_roll_no',
                'admissions.current_class as class_level',
                DB::raw('COUNT(results.id) as failed_subjects_count'),
                DB::raw("GROUP_CONCAT(CONCAT(subjects.name, ' (', (COALESCE(results.obtained_theory, 0) + COALESCE(results.obtained_practical, 0)), '/', results.total_marks, ')') SEPARATOR ', ') as failed_subjects")
            )
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'admissions.id', 'admissions.college_roll_no', 'admissions.current_class')
            ->havingRaw('COUNT(results.id) > 0')
            ->orderByRaw('CAST(admissions.college_roll_no AS UNSIGNED), admissions.college_roll_no')
            ->get();

        $resolvedExamName = $examName ?: ($startDate && $endDate ? "Exams ($startDate to $endDate)" : 'Test Exam');

        $existingFines = StudentFine::where('fine_type', 'test_fail')
            ->where('academic_year', $academicYear)
            ->where(function ($q) use ($resolvedExamName) {
                $q->where('exam_name', $resolvedExamName)
                  ->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($resolvedExamName))]);
            })
            ->get()
            ->keyBy('student_id');

        return $records->map(function ($row) use ($rate, $resolvedExamName, $academicYear, $rule, $passPercentage, $existingFines) {
            $count = (int) $row->failed_subjects_count;
            $totalAmount = round($count * $rate, 2);
            $fullName = trim("{$row->first_name} {$row->last_name}");
            $existing = $existingFines->get($row->student_id);

            return [
                'student_id'       => $row->student_id,
                'student_name'     => $fullName,
                'college_roll_no'  => $row->college_roll_no,
                'class_level'      => $row->class_level,
                'admission_id'     => $row->admission_id,
                'fine_type'        => 'test_fail',
                'fine_rule_id'     => $rule?->id,
                'exam_name'        => $resolvedExamName,
                'subject_id'       => null,
                'academic_year'    => $academicYear,
                'period'           => $resolvedExamName,
                'units_count'      => $count,
                'amount_per_unit'  => $rate,
                'total_amount'     => $totalAmount,
                'reason'           => "Failed in {$count} subject(s) in {$resolvedExamName} (< {$passPercentage}% marks) [{$row->failed_subjects}] (Rs. " . number_format($rate, 2) . " / subject)",
                'already_exists'   => $existing !== null,
                'existing_fine'    => $existing,
            ];
        });
    }

    /**
     * Bulk generate or update fines from preview items.
     *
     * @return array [ 'created' => count, 'updated' => count ]
     */
    public function generate(array $items, int $userId): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($items, $userId, &$created, &$updated) {
            foreach ($items as $item) {
                // Determine unique match keys
                $match = [
                    'student_id'    => $item['student_id'],
                    'fine_type'     => $item['fine_type'],
                    'academic_year' => $item['academic_year'],
                ];

                if ($item['fine_type'] === 'lecture_absent') {
                    $match['period'] = $item['period'];
                } else {
                    $match['exam_name'] = $item['exam_name'];
                }

                $existing = StudentFine::where($match)->first();

                if ($existing) {
                    // Update fine if currently unpaid
                    if ($existing->status === 'unpaid') {
                        $existing->update([
                            'fine_rule_id'    => $item['fine_rule_id'] ?? $existing->fine_rule_id,
                            'admission_id'    => $item['admission_id'] ?? $existing->admission_id,
                            'units_count'     => $item['units_count'],
                            'amount_per_unit' => $item['amount_per_unit'],
                            'total_amount'    => $item['total_amount'],
                            'balance_amount'  => $item['total_amount'],
                            'reason'          => $item['reason'],
                            'generated_by'    => $userId,
                        ]);
                        $updated++;
                    }
                } else {
                    StudentFine::create([
                        'student_id'      => $item['student_id'],
                        'admission_id'    => $item['admission_id'] ?? null,
                        'fine_rule_id'    => $item['fine_rule_id'] ?? null,
                        'fine_type'       => $item['fine_type'],
                        'exam_name'       => $item['exam_name'] ?? null,
                        'subject_id'      => $item['subject_id'] ?? null,
                        'academic_year'   => $item['academic_year'],
                        'period'          => $item['period'] ?? null,
                        'units_count'     => $item['units_count'],
                        'amount_per_unit' => $item['amount_per_unit'],
                        'total_amount'    => $item['total_amount'],
                        'paid_amount'     => 0.00,
                        'waived_amount'   => 0.00,
                        'balance_amount'  => $item['total_amount'],
                        'status'          => 'unpaid',
                        'reason'          => $item['reason'],
                        'generated_by'    => $userId,
                    ]);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Get aggregate statistics.
     */
    public function getStats(?int $year = null): array
    {
        $query = StudentFine::query();
        if ($year) {
            $query->where('academic_year', $year);
        }

        return [
            'total_fines'      => (clone $query)->count(),
            'unpaid_fines'     => (clone $query)->whereIn('status', ['unpaid', 'partially_paid'])->count(),
            'paid_fines'       => (clone $query)->where('status', 'paid')->count(),
            'waived_fines'     => (clone $query)->where('status', 'waived')->count(),
            'total_amount'     => (float) (clone $query)->sum('total_amount'),
            'paid_amount'      => (float) (clone $query)->sum('paid_amount'),
            'waived_amount'    => (float) (clone $query)->sum('waived_amount'),
            'balance_amount'   => (float) (clone $query)->sum(DB::raw('COALESCE(total_amount, 0) - COALESCE(paid_amount, 0) - COALESCE(waived_amount, 0)')),
        ];
    }
}
