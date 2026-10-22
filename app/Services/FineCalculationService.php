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
     * Get available exam names from results and fine_rules tables.
     */
    public function getAvailableExamNames(): Collection
    {
        $examNamesFromResults = DB::table('results')
            ->select('exam_name')
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->distinct()
            ->pluck('exam_name');

        $examNamesFromRules = DB::table('fine_rules')
            ->select('exam_name')
            ->whereNotNull('exam_name')
            ->where('exam_name', '!=', '')
            ->distinct()
            ->pluck('exam_name');

        return $examNamesFromResults
            ->merge($examNamesFromRules)
            ->merge(['October Test', 'December Test'])
            ->unique()
            ->values();
    }

    /**
     * Calculate and preview fine items for the given criteria.
     */
    public function preview(array $filters): Collection
    {
        $fineType = $filters['fine_type'] ?? 'lecture_absent';
        $academicYear = (int) ($filters['academic_year'] ?? date('Y'));
        $classLevel = $filters['class_level'] ?? null; // '11', '12', or null/all
        $examName = $filters['exam_name'] ?? null;
        $period = $filters['period'] ?? now()->format('Y-m'); // e.g. "2026-10"

        // Determine rate and pass percentage
        $rule = null;
        if (!empty($filters['fine_rule_id'])) {
            $rule = FineRule::find($filters['fine_rule_id']);
            if ($rule) {
                $fineType = $rule->fine_type;
                if ($rule->exam_name) {
                    $examName = $rule->exam_name;
                }
            }
        }

        $rate = isset($filters['rate']) && $filters['rate'] !== ''
            ? (float) $filters['rate']
            : ($rule ? (float) $rule->amount_per_unit : 1.00);

        $passPercentage = isset($filters['pass_percentage']) && $filters['pass_percentage'] !== ''
            ? (float) $filters['pass_percentage']
            : ($rule ? (float) $rule->pass_percentage : 33.00);

        return match ($fineType) {
            'lecture_absent' => $this->previewLectureAbsentFines($period, $academicYear, $classLevel, $rate, $rule),
            'test_absent'    => $this->previewTestAbsentFines($examName, $academicYear, $classLevel, $rate, $rule),
            'test_fail'      => $this->previewTestFailFines($examName, $academicYear, $classLevel, $rate, $passPercentage, $rule),
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
    protected function previewTestAbsentFines(?string $examName, int $academicYear, ?string $classLevel, float $rate, ?FineRule $rule): Collection
    {
        if (!$examName) {
            return collect();
        }

        $query = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('subjects', 'results.subject_id', '=', 'subjects.id')
            ->join('admissions', function ($join) {
                $join->on('students.id', '=', 'admissions.student_id')
                    ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted']);
            })
            ->where('results.exam_name', $examName)
            ->where('results.academic_year', $academicYear)
            ->where('results.is_absent', 1)
            ->whereNull('results.deleted_at');

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
                DB::raw('COUNT(results.id) as absent_papers_count'),
                DB::raw("GROUP_CONCAT(subjects.name SEPARATOR ', ') as missed_subjects")
            )
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'admissions.id', 'admissions.college_roll_no', 'admissions.current_class')
            ->havingRaw('COUNT(results.id) > 0')
            ->orderBy('admissions.college_roll_no')
            ->get();

        $existingFines = StudentFine::where('fine_type', 'test_absent')
            ->where('academic_year', $academicYear)
            ->where('exam_name', $examName)
            ->get()
            ->keyBy('student_id');

        return $records->map(function ($row) use ($rate, $examName, $academicYear, $rule, $existingFines) {
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
                'exam_name'        => $examName,
                'subject_id'       => null,
                'academic_year'    => $academicYear,
                'period'           => $examName,
                'units_count'      => $count,
                'amount_per_unit'  => $rate,
                'total_amount'     => $totalAmount,
                'reason'           => "Absent from {$count} paper(s) in {$examName} [{$row->missed_subjects}] (Rs. " . number_format($rate, 2) . " / paper)",
                'already_exists'   => $existing !== null,
                'existing_fine'    => $existing,
            ];
        });
    }

    /**
     * Preview Test Failure Fines.
     */
    protected function previewTestFailFines(?string $examName, int $academicYear, ?string $classLevel, float $rate, float $passPercentage, ?FineRule $rule): Collection
    {
        if (!$examName) {
            return collect();
        }

        $query = DB::table('results')
            ->join('students', 'results.student_id', '=', 'students.id')
            ->join('subjects', 'results.subject_id', '=', 'subjects.id')
            ->join('admissions', function ($join) {
                $join->on('students.id', '=', 'admissions.student_id')
                    ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted']);
            })
            ->where('results.exam_name', $examName)
            ->where('results.academic_year', $academicYear)
            ->where('results.is_absent', 0)
            ->where('results.total_marks', '>', 0)
            ->whereRaw("((COALESCE(results.obtained_theory, 0) + COALESCE(results.obtained_practical, 0)) / results.total_marks) * 100 < ?", [$passPercentage])
            ->whereNull('results.deleted_at');

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
                DB::raw('COUNT(results.id) as failed_subjects_count'),
                DB::raw("GROUP_CONCAT(CONCAT(subjects.name, ' (', (COALESCE(results.obtained_theory, 0) + COALESCE(results.obtained_practical, 0)), '/', results.total_marks, ')') SEPARATOR ', ') as failed_subjects")
            )
            ->groupBy('students.id', 'students.first_name', 'students.last_name', 'admissions.id', 'admissions.college_roll_no', 'admissions.current_class')
            ->havingRaw('COUNT(results.id) > 0')
            ->orderBy('admissions.college_roll_no')
            ->get();

        $existingFines = StudentFine::where('fine_type', 'test_fail')
            ->where('academic_year', $academicYear)
            ->where('exam_name', $examName)
            ->get()
            ->keyBy('student_id');

        return $records->map(function ($row) use ($rate, $examName, $academicYear, $rule, $passPercentage, $existingFines) {
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
                'exam_name'        => $examName,
                'subject_id'       => null,
                'academic_year'    => $academicYear,
                'period'           => $examName,
                'units_count'      => $count,
                'amount_per_unit'  => $rate,
                'total_amount'     => $totalAmount,
                'reason'           => "Failed in {$count} subject(s) in {$examName} (< {$passPercentage}% marks) [{$row->failed_subjects}] (Rs. " . number_format($rate, 2) . " / subject)",
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
