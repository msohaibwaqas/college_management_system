<?php

namespace App\Services;

use App\Models\ExamConductor;
use App\Models\HeDatesheet;
use App\Models\HeSeatingPlan;
use App\Models\InvigilatorAssignment;
use App\Models\InvigilatorDutyRule;
use App\Models\SeatingPlan;
use App\Models\Staff;
use App\Models\StudentTeacherSubject;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * InvigilationService centralizes duty calculations, staff roster formatting,
 * room assignment synchronization, auto-assignment algorithms, and duty roster
 * reporting for both HSSC (Intermediate) and Higher Education (BS/ADP).
 */
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
     * Sort staff roster array collection by academic designation hierarchy, then by name.
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
     * Sort a collection of Staff models by academic designation hierarchy, then by name.
     */
    public function sortStaffCollectionByDesignation(Collection $staff): Collection
    {
        $designationOrder = [
            'professor'           => 1,
            'associate professor' => 2,
            'assistant professor' => 3,
            'lecturer'            => 4,
        ];

        return $staff->sortBy(function ($item) use ($designationOrder) {
            $desig = strtolower(trim($item->designationModel->name ?? $item->designation ?? ''));
            return $designationOrder[$desig] ?? 999;
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

    // ─── Auto-Assignment Engine ─────────────────────────────────────

    /**
     * Execute auto-assignment of invigilators for HSSC seating plans in date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $invigilatorsPerRoom
     * @param array $exemptedStaffIds
     * @return array Detailed assignment report
     * @throws \RuntimeException
     */
    public function autoAssignHssc(
        Carbon $startDate,
        Carbon $endDate,
        int $invigilatorsPerRoom,
        array $exemptedStaffIds = []
    ): array {
        $lockTimeout = 300; // 5 minutes
        $locked = DB::table('seating_plans')
            ->where('assignment_in_progress', true)
            ->where('assignment_locked_at', '>', now()->subSeconds($lockTimeout))
            ->exists();

        if ($locked) {
            throw new \RuntimeException('Another assignment operation is in progress. Please wait and try again.');
        }

        DB::beginTransaction();
        try {
            // Set lock on all seating plans in date range
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate, $endDate])
                ->update([
                    'assignment_in_progress' => true,
                    'assignment_locked_at'   => now(),
                ]);

            $hsscPlans = SeatingPlan::whereBetween('test_date', [$startDate, $endDate])->get();
            if ($hsscPlans->isEmpty()) {
                throw new \RuntimeException('No seating plans found for the selected date range.');
            }

            $plansWithoutRooms = $hsscPlans->filter(fn($plan) => $plan->details()->count() == 0);
            if ($plansWithoutRooms->isNotEmpty()) {
                throw new \RuntimeException('Some seating plans have no rooms assigned. Please fix before auto-assignment.');
            }

            // Eligible teachers
            $exemptedFromConductors = ExamConductor::getExemptedStaffIds($startDate, $endDate);
            $allExempted = array_unique(array_merge($exemptedStaffIds, $exemptedFromConductors));

            $eligibleTeachers = Staff::teaching()
                ->active()
                ->whereNotNull('designation_id')
                ->whereNotIn('id', $allExempted)
                ->with('designationModel')
                ->get();

            if ($eligibleTeachers->isEmpty()) {
                throw new \RuntimeException('No eligible teachers found for invigilator duty.');
            }

            $teachersWithoutRules = $eligibleTeachers->filter(function ($teacher) {
                return $teacher->designation_id === null || InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) === null;
            });

            $warnings = [];
            if ($teachersWithoutRules->isNotEmpty()) {
                $warnings[] = 'Warning: ' . $teachersWithoutRules->count() . ' teachers have no duty rules set. They will use default limit (999 days).';
            }

            // Initialize duty tracking
            $dutyCount = [];
            $mandatoryAssignments = [];

            foreach ($eligibleTeachers as $teacher) {
                $dutyCount[$teacher->id] = [
                    'count'   => 0,
                    'max'     => InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) ?? 999,
                    'teacher' => $teacher,
                ];
            }

            $dailyAssignments = [];
            $allAssignments = [];
            $teachersOverLimit = [];
            $roomsUnderStaffed = [];
            $roomSlots = [];

            // Step 1: Build list of room slots
            foreach ($hsscPlans as $plan) {
                $rooms = $plan->details->groupBy('room_id');
                $dateKey = $plan->test_date->format('Y-m-d');

                $studentIds = $plan->details->pluck('student_id')->unique();
                $subjectIds = $plan->details->pluck('subject_id')->unique();
                $teacherMappings = StudentTeacherSubject::whereIn('student_id', $studentIds)
                    ->whereIn('subject_id', $subjectIds)
                    ->get()
                    ->groupBy('student_id');

                foreach ($rooms as $roomId => $details) {
                    $teacherCounts = [];
                    foreach ($details as $detail) {
                        $mapping = $teacherMappings->get($detail->student_id)?->where('subject_id', $detail->subject_id)?->first();
                        $teacherId = $mapping?->staff_id;
                        if ($teacherId) {
                            $teacherCounts[$teacherId] = ($teacherCounts[$teacherId] ?? 0) + 1;
                        }
                    }

                    arsort($teacherCounts);
                    $preferredTeachers = array_keys($teacherCounts);

                    for ($i = 0; $i < $invigilatorsPerRoom; $i++) {
                        $roomSlots[] = [
                            'plan_id'            => $plan->id,
                            'room_id'            => $roomId,
                            'date_key'           => $dateKey,
                            'exam_date'          => $plan->test_date,
                            'priority'           => $plan->test_date->timestamp,
                            'preferred_teachers' => $preferredTeachers,
                        ];
                    }
                }
            }

            // Step 2: Sort room slots by priority
            usort($roomSlots, fn($a, $b) => $b['priority'] - $a['priority']);

            // Step 3: Priority Pass — preferred teachers
            $remainingSlots = [];
            foreach ($roomSlots as $slot) {
                $dateKey = $slot['date_key'];
                $assigned = false;

                if (!isset($dailyAssignments[$dateKey])) {
                    $dailyAssignments[$dateKey] = [];
                }

                foreach ($slot['preferred_teachers'] as $staffId) {
                    if (isset($dutyCount[$staffId]) &&
                        !isset($dailyAssignments[$dateKey][$staffId]) &&
                        $dutyCount[$staffId]['count'] < $dutyCount[$staffId]['max']) {

                        $allAssignments[] = [
                            'room_id'          => $slot['room_id'],
                            'staff_id'         => $staffId,
                            'exam_date'        => $slot['exam_date'],
                            'is_auto_assigned' => true,
                            'seating_plan_id'  => $slot['plan_id'],
                        ];
                        $dutyCount[$staffId]['count']++;
                        $dailyAssignments[$dateKey][$staffId] = true;
                        $assigned = true;
                        break;
                    }
                }

                if (!$assigned) {
                    $remainingSlots[] = $slot;
                }
            }

            // Step 4: Balance Pass — round-robin filling
            foreach ($remainingSlots as $slot) {
                $dateKey = $slot['date_key'];
                $bestTeacher = null;
                $lowestDutyCount = PHP_INT_MAX;

                foreach ($dutyCount as $staffId => $data) {
                    if (!isset($dailyAssignments[$dateKey][$staffId]) &&
                        $data['count'] < $data['max']) {

                        if ($data['count'] < $lowestDutyCount) {
                            $bestTeacher = $staffId;
                            $lowestDutyCount = $data['count'];
                        }
                    }
                }

                if ($bestTeacher !== null) {
                    $allAssignments[] = [
                        'room_id'          => $slot['room_id'],
                        'staff_id'         => $bestTeacher,
                        'exam_date'        => $slot['exam_date'],
                        'is_auto_assigned' => true,
                        'seating_plan_id'  => $slot['plan_id'],
                    ];
                    $dutyCount[$bestTeacher]['count']++;
                    $dailyAssignments[$dateKey][$bestTeacher] = true;
                }
            }

            // Check for teachers exceeding limits
            foreach ($dutyCount as $staffId => $data) {
                if ($data['count'] > $data['max']) {
                    $teachersOverLimit[] = [
                        'name'     => $data['teacher']->first_name . ' ' . $data['teacher']->last_name,
                        'assigned' => $data['count'],
                        'limit'    => $data['max'],
                        'excess'   => $data['count'] - $data['max'],
                    ];
                }
            }

            // Cleanup orphaned records & existing auto-assignments
            $this->cleanupOrphanedRecords($startDate, $endDate);

            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->where('is_auto_assigned', true)
                ->delete();

            if (!empty($allAssignments)) {
                InvigilatorAssignment::insert($allAssignments);
            }

            // Release lock
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate, $endDate])
                ->update([
                    'assignment_in_progress' => false,
                    'assignment_locked_at'   => null,
                ]);

            DB::commit();

            // Daily stats calculation
            $dailyStats = [];
            foreach ($hsscPlans as $plan) {
                $dateKey = $plan->test_date->format('Y-m-d');
                $rooms = $plan->details->groupBy('room_id');
                $totalRooms = $rooms->count();
                $neededInvigilators = $totalRooms * $invigilatorsPerRoom;

                $availableTeachersCount = collect($dutyCount)->filter(function ($data, $staffId) use ($dailyAssignments, $dateKey) {
                    return !isset($dailyAssignments[$dateKey][$staffId]) &&
                           $data['count'] < $data['max'];
                })->count();

                $excludedByLimitCount = collect($dutyCount)->filter(function ($data, $staffId) use ($dailyAssignments, $dateKey) {
                    return !isset($dailyAssignments[$dateKey][$staffId]) &&
                           $data['count'] >= $data['max'];
                })->count();

                $assignedToday = isset($dailyAssignments[$dateKey]) ? count($dailyAssignments[$dateKey]) : 0;

                $dailyStats[$dateKey] = [
                    'date'                  => $plan->test_date->format('d M Y'),
                    'total_rooms'           => $totalRooms,
                    'needed_invigilators'   => $neededInvigilators,
                    'assigned_invigilators' => $assignedToday,
                    'available_teachers'    => $availableTeachersCount,
                    'excluded_by_limit'     => $excludedByLimitCount,
                    'shortage'              => max(0, $neededInvigilators - $assignedToday),
                ];
            }

            return $this->generateAssignmentReport(
                $allAssignments,
                $mandatoryAssignments,
                $dutyCount,
                $roomsUnderStaffed,
                $teachersOverLimit,
                $warnings,
                $dailyStats
            );

        } catch (\Exception $e) {
            DB::table('seating_plans')
                ->whereBetween('test_date', [$startDate, $endDate])
                ->update([
                    'assignment_in_progress' => false,
                    'assignment_locked_at'   => null,
                ]);

            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Execute auto-assignment of invigilators for Higher Education (BS/ADP) seating plans.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $perRoom
     * @return void
     * @throws \RuntimeException
     */
    public function autoAssignHe(Carbon $startDate, Carbon $endDate, int $perRoom): void
    {
        DB::beginTransaction();
        try {
            $hePlans = HeSeatingPlan::whereHas('datesheet', fn($q) => $q->whereBetween('exam_date', [$startDate, $endDate]))
                ->with('datesheet')->get();

            if ($hePlans->isEmpty()) {
                throw new \RuntimeException('No HE seating plans found for the selected date range.');
            }

            $eligibleTeachers = Staff::teaching()->active()->whereNotNull('designation_id')->with('designationModel')->get();
            $dutyCount = [];
            foreach ($eligibleTeachers as $teacher) {
                $dutyCount[$teacher->id] = [
                    'count'   => InvigilatorAssignment::where('staff_id', $teacher->id)->count(),
                    'max'     => InvigilatorDutyRule::getMaxDutyDays($teacher->designation_id) ?? 15,
                    'teacher' => $teacher,
                ];
            }

            $roomSlots = [];
            foreach ($hePlans as $plan) {
                $rooms = $plan->details->groupBy('room_id');
                $dateKey = $plan->datesheet->exam_date->format('Y-m-d');
                foreach ($rooms as $roomId => $details) {
                    for ($i = 0; $i < $perRoom; $i++) {
                        $roomSlots[] = [
                            'plan_id'   => $plan->id,
                            'room_id'   => $roomId,
                            'date_key'  => $dateKey,
                            'exam_date' => $plan->datesheet->exam_date,
                        ];
                    }
                }
            }

            $allAssignments = [];
            $dailyAssignments = [];

            foreach ($roomSlots as $slot) {
                $dateKey = $slot['date_key'];
                $bestTeacher = null;
                $lowestCount = PHP_INT_MAX;

                foreach ($dutyCount as $id => $data) {
                    if (!isset($dailyAssignments[$dateKey][$id]) && $data['count'] < $data['max']) {
                        if ($data['count'] < $lowestCount) {
                            $bestTeacher = $id;
                            $lowestCount = $data['count'];
                        }
                    }
                }

                if ($bestTeacher) {
                    $allAssignments[] = [
                        'room_id'            => $slot['room_id'],
                        'staff_id'           => $bestTeacher,
                        'exam_date'          => $slot['exam_date'],
                        'is_auto_assigned'   => true,
                        'he_seating_plan_id' => $slot['plan_id'],
                    ];
                    $dutyCount[$bestTeacher]['count']++;
                    $dailyAssignments[$dateKey][$bestTeacher] = true;
                }
            }

            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->where('is_auto_assigned', true)
                ->whereNotNull('he_seating_plan_id')
                ->delete();

            if (!empty($allAssignments)) {
                InvigilatorAssignment::insert($allAssignments);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ─── Roster & Reporting ─────────────────────────────────────────

    /**
     * Get unique exam dates for roster view.
     */
    public function getRosterDates(string $subsystem = 'HSSC'): Collection
    {
        if ($subsystem === 'HE') {
            return InvigilatorAssignment::whereNotNull('he_seating_plan_id')
                ->select('exam_date')->distinct()->orderBy('exam_date')->get()
                ->map(fn($a) => (object)['exam_date' => Carbon::parse($a->exam_date)]);
        }

        return InvigilatorAssignment::select('exam_date')
            ->distinct()->orderBy('exam_date')->get()
            ->map(fn($a) => (object)['exam_date' => Carbon::parse($a->exam_date)]);
    }

    /**
     * Group invigilation assignments by room and sort staff by designation order.
     */
    public function getDutyRoster(string $date, string $subsystem = 'HSSC'): Collection
    {
        if ($subsystem === 'HE') {
            return InvigilatorAssignment::where('exam_date', $date)
                ->whereNotNull('he_seating_plan_id')
                ->with(['room', 'staff.designationModel', 'heSeatingPlan.datesheet.bsSubject'])
                ->get()
                ->groupBy(fn($a) => "he_{$a->he_seating_plan_id}_{$a->room_id}")
                ->map(function ($group) {
                    $first = $group->first();
                    $subjectName = $first->heSeatingPlan->datesheet->bsSubject->name ?? 'HE';
                    return [
                        'room_name' => "{$first->room->name} ({$subjectName})",
                        'staff'     => $this->sortStaffCollectionByDesignation($group->pluck('staff')),
                    ];
                })->sortBy('room_name')->values();
        }

        return InvigilatorAssignment::where('exam_date', $date)
            ->with(['room', 'staff.designationModel', 'seatingPlan'])
            ->get()
            ->groupBy(fn($a) => "hssc_{$a->seating_plan_id}_{$a->room_id}")
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'room_name' => $first->room->name,
                    'staff'     => $this->sortStaffCollectionByDesignation($group->pluck('staff')),
                ];
            })->sortBy('room_name')->values();
    }

    /**
     * Cleanup orphaned invigilator assignment records.
     */
    public function cleanupOrphanedRecords(Carbon $startDate, Carbon $endDate): void
    {
        // Remove assignments for deleted staff
        $deletedStaffIds = DB::table('staff')->whereNotNull('deleted_at')->pluck('id');
        if ($deletedStaffIds->isNotEmpty()) {
            InvigilatorAssignment::whereIn('staff_id', $deletedStaffIds)->delete();
        }

        // Remove assignments for dates with no seating plans
        $validPlanIds = SeatingPlan::whereBetween('test_date', [$startDate, $endDate])->pluck('id');
        if ($validPlanIds->isNotEmpty()) {
            InvigilatorAssignment::whereBetween('exam_date', [$startDate, $endDate])
                ->whereNotIn('seating_plan_id', $validPlanIds)
                ->delete();
        }
    }

    /**
     * Generate detailed assignment report data structure.
     */
    public function generateAssignmentReport(
        array $allAssignments,
        array $mandatoryAssignments,
        array $dutyCount,
        array $roomsUnderStaffed,
        array $teachersOverLimit,
        array $warnings,
        array $dailyStats = []
    ): array {
        $report = [
            'total_assignments'     => count($allAssignments),
            'mandatory_assignments' => count($mandatoryAssignments),
            'optional_assignments'  => count($allAssignments) - count($mandatoryAssignments),
            'warnings'              => $warnings,
            'rooms_understaffed'    => $roomsUnderStaffed,
            'teachers_over_limit'   => $teachersOverLimit,
            'daily_stats'           => $dailyStats,
            'duty_distribution'     => [],
        ];

        $dutyByDesignation = [];
        foreach ($dutyCount as $staffId => $data) {
            $designation = $data['teacher']->designationModel->name ?? 'Unknown';
            if (!isset($dutyByDesignation[$designation])) {
                $dutyByDesignation[$designation] = [
                    'total_teachers' => 0,
                    'total_duties'   => 0,
                    'avg_duties'     => 0,
                    'max_limit'      => $data['max'],
                ];
            }
            $dutyByDesignation[$designation]['total_teachers']++;
            $dutyByDesignation[$designation]['total_duties'] += $data['count'];
        }

        foreach ($dutyByDesignation as $designation => &$stats) {
            $stats['avg_duties'] = round($stats['total_duties'] / $stats['total_teachers'], 2);
        }

        $report['duty_distribution'] = $dutyByDesignation;

        return $report;
    }
}
