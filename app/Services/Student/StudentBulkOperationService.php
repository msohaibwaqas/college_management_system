<?php

namespace App\Services\Student;

use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentBulkOperationService
{
    /**
     * Bulk delete students and their associated admissions.
     */
    public function deleteStudents(array $studentIds): int
    {
        if (empty($studentIds)) {
            throw new InvalidArgumentException('No students selected for deletion.');
        }

        return DB::transaction(function () use ($studentIds) {
            return Student::whereIn('id', $studentIds)->delete();
        });
    }

    /**
     * Bulk delete HE admissions (and optionally linked student records).
     */
    public function deleteHeAdmissions(array $admissionIds = [], array $studentIds = []): int
    {
        if (empty($admissionIds) && !empty($studentIds)) {
            $admissionIds = HeAdmission::whereIn('student_id', $studentIds)->pluck('id')->toArray();
        }

        if (empty($admissionIds) && empty($studentIds)) {
            throw new InvalidArgumentException('No HE admissions selected for deletion.');
        }

        return DB::transaction(function () use ($admissionIds, $studentIds) {
            $deletedCount = 0;
            if (!empty($admissionIds)) {
                $deletedCount = HeAdmission::whereIn('id', $admissionIds)->delete();
            }

            if (!empty($studentIds)) {
                Student::whereIn('id', $studentIds)->delete();
            }

            return $deletedCount;
        });
    }

    /**
     * Bulk update status for HSSC, HE, or all student admissions.
     */
    public function updateStatus(
        array $studentIds = [],
        string $status = 'Active',
        array $admissionIds = [],
        string $scope = 'all',
        ?int $userId = null
    ): int {
        if (empty($studentIds) && empty($admissionIds)) {
            throw new InvalidArgumentException('No students or admissions selected.');
        }

        return DB::transaction(function () use ($studentIds, $status, $admissionIds, $scope, $userId) {
            $updatedCount = 0;

            if ($scope === 'all' || $scope === 'hssc') {
                $hsscQuery = Admissions::query();
                if (!empty($admissionIds) && $scope === 'hssc') {
                    $hsscQuery->whereIn('id', $admissionIds);
                } elseif (!empty($studentIds)) {
                    $hsscQuery->whereIn('student_id', $studentIds);
                }
                $updatedCount += $hsscQuery->update([
                    'status'     => $status,
                    'updated_by' => $userId,
                ]);
            }

            if ($scope === 'all' || $scope === 'he') {
                $heQuery = HeAdmission::query();
                if (!empty($admissionIds)) {
                    $heQuery->whereIn('id', $admissionIds);
                } elseif (!empty($studentIds)) {
                    $heQuery->whereIn('student_id', $studentIds);
                }
                $updatedCount += $heQuery->update([
                    'status'     => $status,
                    'updated_by' => $userId,
                ]);
            }

            return $updatedCount;
        });
    }

    /**
     * Bulk promote HSSC students from Class 11 to Class 12.
     */
    public function promoteHssc(array $studentIds, ?int $userId = null): int
    {
        if (empty($studentIds)) {
            throw new InvalidArgumentException('No students selected for promotion.');
        }

        return DB::transaction(function () use ($studentIds, $userId) {
            $admissions = Admissions::whereIn('student_id', $studentIds)
                ->where('current_class', '11')
                ->whereIn('status', ['Admitted', 'Active', 'admitted', 'active'])
                ->get();

            $promotedCount = 0;
            foreach ($admissions as $admission) {
                if ($admission->promoteToClass12()) {
                    if ($userId) {
                        $admission->update(['updated_by' => $userId]);
                    }
                    $promotedCount++;
                }
            }

            return $promotedCount;
        });
    }

    /**
     * Bulk promote HE students to next semester, specific semester, or graduate.
     *
     * @return array{promoted_count: int, graduated_count: int, total_processed: int}
     */
    public function promoteHe(
        array $admissionIds = [],
        array $studentIds = [],
        string $action = 'next_semester',
        ?int $targetSemester = null,
        ?int $userId = null
    ): array {
        $query = HeAdmission::query();
        if (!empty($admissionIds)) {
            $query->whereIn('id', $admissionIds);
        } elseif (!empty($studentIds)) {
            $query->whereIn('student_id', $studentIds);
        } else {
            throw new InvalidArgumentException('No HE students selected for promotion.');
        }

        return DB::transaction(function () use ($query, $action, $targetSemester, $userId) {
            $admissions = $query->get();
            $promotedCount = 0;
            $graduatedCount = 0;

            foreach ($admissions as $admission) {
                if ($action === 'graduate') {
                    $admission->graduate($userId);
                    $graduatedCount++;
                } elseif ($action === 'specific_semester' && $targetSemester) {
                    if ($admission->promoteToNextSemester($targetSemester, $userId)) {
                        $promotedCount++;
                    }
                } else {
                    // Next semester
                    if ($admission->current_semester >= 8) {
                        $admission->graduate($userId);
                        $graduatedCount++;
                    } else {
                        if ($admission->promoteToNextSemester(null, $userId)) {
                            $promotedCount++;
                        }
                    }
                }
            }

            return [
                'promoted_count'  => $promotedCount,
                'graduated_count' => $graduatedCount,
                'total_processed' => $admissions->count(),
            ];
        });
    }
}
