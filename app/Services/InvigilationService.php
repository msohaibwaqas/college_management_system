<?php

namespace App\Services;

use App\Models\ExamConductor;
use App\Models\InvigilatorAssignment;
use App\Models\InvigilatorDutyRule;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvigilationService
{
    /**
     * Get the count of total invigilation duties for each staff member in a single query.
     */
    public function getDutyCounts(): array
    {
        return InvigilatorAssignment::select('staff_id', DB::raw('COUNT(*) as total'))
            ->groupBy('staff_id')
            ->pluck('total', 'staff_id')
            ->toArray();
    }

    /**
     * Check if a staff member has an active conductor exemption on a given date.
     */
    public function isStaffExempted(int $staffId, string $examDate): bool
    {
        return ExamConductor::where('staff_id', $staffId)
            ->where(function ($query) use ($examDate) {
                $query->whereNull('exam_start_date')
                      ->orWhere(function ($q) use ($examDate) {
                          $q->where('exam_start_date', '<=', $examDate)
                            ->where('exam_end_date', '>=', $examDate);
                      });
            })
            ->exists();
    }

    /**
     * Get sorted and formatted staff roster with duty counts, max limits, and exemptions.
     */
    public function getStaffRoster(): Collection
    {
        $staff = Staff::teaching()->active()->with('designationModel')->get();
        $dutyCounts = $this->getDutyCounts();
        $exemptions = ExamConductor::with('staff')->get();
        $dutyRules = InvigilatorDutyRule::with('designation')->get()->keyBy('designation_id');

        $staffData = $staff->map(function ($member) use ($dutyCounts, $dutyRules, $exemptions) {
            $dutyCount = $dutyCounts[$member->id] ?? 0;
            $maxLimit = ($member->designation_id && isset($dutyRules[$member->designation_id]))
                ? $dutyRules[$member->designation_id]->max_duty_days
                : 15;
            $isExempted = $exemptions->where('staff_id', $member->id)->isNotEmpty();
            $designationName = $member->designationModel->name ?? $member->designation ?? 'NO DESIGNATION';

            return [
                'id'          => $member->id,
                'name'        => trim($member->first_name . ' ' . ($member->last_name ?? '')),
                'designation' => $designationName,
                'duty_count'  => $dutyCount,
                'max_limit'   => $maxLimit,
                'is_exempted' => $isExempted,
            ];
        });

        return $this->sortRosterByDesignation($staffData);
    }

    /**
     * Sort staff roster by academic designation hierarchy, then by name.
     */
    public function sortRosterByDesignation(Collection $roster): Collection
    {
        $designationOrder = [
            'professor'           => 1,
            'associate professor' => 2,
            'assistant professor' => 3,
            'lecturer'            => 4,
        ];

        return $roster->sort(function ($a, $b) use ($designationOrder) {
            $desigA = strtolower(trim($a['designation'] ?? ''));
            $desigB = strtolower(trim($b['designation'] ?? ''));

            $orderA = $designationOrder[$desigA] ?? 999;
            $orderB = $designationOrder[$desigB] ?? 999;

            if ($orderA === $orderB) {
                return strcmp($a['name'] ?? '', $b['name'] ?? '');
            }

            return $orderA - $orderB;
        })->values();
    }

    /**
     * Synchronize invigilation room assignments for a plan (HSSC seating_plan_id or HE he_seating_plan_id).
     */
    public function syncRoomAssignments(
        string $planColumn,
        int $planId,
        int $roomId,
        string $examDate,
        array $newStaffIds
    ): array {
        $existingAssignments = InvigilatorAssignment::where('room_id', $roomId)
            ->where('exam_date', $examDate)
            ->where($planColumn, $planId)
            ->get();

        $currentStaffIds = $existingAssignments->pluck('staff_id')->toArray();

        $deletedCount = 0;
        $successCount = 0;
        $errors = [];

        // Identify staff to remove (in DB but not in request)
        $toRemove = array_diff($currentStaffIds, $newStaffIds);
        if (!empty($toRemove)) {
            $deletedCount = InvigilatorAssignment::where('room_id', $roomId)
                ->where('exam_date', $examDate)
                ->whereIn('staff_id', $toRemove)
                ->where($planColumn, $planId)
                ->delete();
        }

        // Identify staff to add (in request but not in DB)
        $toAdd = array_diff($newStaffIds, $currentStaffIds);
        foreach ($toAdd as $staffId) {
            if ($this->isStaffExempted((int) $staffId, $examDate)) {
                $errors[] = "Staff member (ID: {$staffId}) is exempted on this date.";
                continue;
            }

            InvigilatorAssignment::create([
                'room_id'          => $roomId,
                'staff_id'         => $staffId,
                'exam_date'        => $examDate,
                'is_auto_assigned' => false,
                $planColumn        => $planId,
            ]);
            $successCount++;
        }

        return [
            'added'   => $successCount,
            'removed' => $deletedCount,
            'errors'  => $errors,
        ];
    }
}
