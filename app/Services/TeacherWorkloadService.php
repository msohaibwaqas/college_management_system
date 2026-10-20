<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TeacherWorkloadService encapsulates the data-intensive aggregation
 * queries for the teacher workload report, extracted from
 * AttendanceReportController::teacherWorkload() (~350 LOC).
 */
class TeacherWorkloadService
{
    /**
     * Fetch the base list of teaching staff, optionally filtered.
     *
     * @return \Illuminate\Support\Collection  staff rows
     * @return \Illuminate\Support\Collection  staff IDs
     */
    public function getFilteredStaff(string $search = '', string $designationFilter = '', string $bpsFilter = ''): array
    {
        $query = DB::table('staff')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('category', 'teaching')
                  ->orWhereIn('id', function ($sub) {
                      $sub->select('staff_id')->from('student_teacher_subjects')->whereNull('deleted_at');
                  })
                  ->orWhereIn('id', function ($sub) {
                      $sub->select('staff_id')->from('timetable_entries')->where('is_active', 1);
                  })
                  ->orWhereIn('id', function ($sub) {
                      $sub->select('staff_id')->from('he_teacher_assignments');
                  });
            });

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%");
            });
        }

        if (!empty($designationFilter)) {
            $query->where('designation', $designationFilter);
        }

        if (!empty($bpsFilter)) {
            $query->where('bps', $bpsFilter);
        }

        $staffList = $query->orderBy('first_name')->orderBy('last_name')->get();
        $staffIds = $staffList->pluck('id');

        return [$staffList, $staffIds];
    }

    /**
     * Get distinct designation values for the filter dropdown.
     */
    public function getDesignationOptions(): Collection
    {
        return DB::table('staff')
            ->whereNull('deleted_at')
            ->whereNotNull('designation')
            ->where('designation', '!=', '')
            ->distinct()
            ->orderBy('designation')
            ->pluck('designation');
    }

    /**
     * Get distinct BPS values for the filter dropdown.
     */
    public function getBpsOptions(): Collection
    {
        return DB::table('staff')
            ->whereNull('deleted_at')
            ->whereNotNull('bps')
            ->distinct()
            ->orderBy('bps')
            ->pluck('bps');
    }

    /**
     * Aggregate weekly class counts per staff for both HSSC and HE.
     *
     * @return array{Collection, Collection}  [hsscPeriods, hePeriods]
     */
    public function getWeeklyClassCounts(Collection $staffIds): array
    {
        $hsscPeriods = DB::table('timetable_entries')
            ->whereIn('staff_id', $staffIds)
            ->where('is_active', 1)
            ->select('staff_id', DB::raw('COUNT(*) as total_classes'))
            ->groupBy('staff_id')
            ->pluck('total_classes', 'staff_id');

        $hePeriods = DB::table('he_timetables')
            ->whereIn('staff_id', $staffIds)
            ->select('staff_id', DB::raw('COUNT(*) as total_classes'))
            ->groupBy('staff_id')
            ->pluck('total_classes', 'staff_id');

        return [$hsscPeriods, $hePeriods];
    }

    /**
     * Aggregate unique student counts taught per staff for HSSC and HE.
     *
     * @return array{Collection, Collection}  [hsscStudents, heStudents]
     */
    public function getStudentCounts(Collection $staffIds): array
    {
        $hsscStudents = DB::table('student_teacher_subjects')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->whereIn('student_teacher_subjects.staff_id', $staffIds)
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->select('student_teacher_subjects.staff_id', 'student_teacher_subjects.student_id')
            ->get()
            ->groupBy('staff_id');

        $heStudents = DB::table('he_teacher_assignments')
            ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
            ->whereIn('he_teacher_assignments.staff_id', $staffIds)
            ->whereNull('he_admissions.deleted_at')
            ->whereIn('he_admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->select('he_teacher_assignments.staff_id', 'he_teacher_assignments.he_admission_id')
            ->get()
            ->groupBy('staff_id');

        return [$hsscStudents, $heStudents];
    }

    /**
     * Fetch HSSC teaching blocks (subject → class → section breakdown per staff).
     */
    public function getHsscTeachingBlocks(Collection $staffIds): Collection
    {
        return DB::table('student_teacher_subjects')
            ->join('subjects', 'student_teacher_subjects.subject_id', '=', 'subjects.id')
            ->join('students', 'student_teacher_subjects.student_id', '=', 'students.id')
            ->join('admissions', 'students.id', '=', 'admissions.student_id')
            ->leftJoin('student_subject_sections', function ($join) {
                $join->on('student_teacher_subjects.student_id', '=', 'student_subject_sections.student_id')
                     ->on('student_teacher_subjects.subject_id', '=', 'student_subject_sections.subject_id')
                     ->on('admissions.admission_year', '=', 'student_subject_sections.admission_year')
                     ->whereNull('student_subject_sections.deleted_at');
            })
            ->whereIn('student_teacher_subjects.staff_id', $staffIds)
            ->whereIn('admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNull('student_teacher_subjects.deleted_at')
            ->whereNull('students.deleted_at')
            ->whereNull('admissions.deleted_at')
            ->select(
                'student_teacher_subjects.staff_id',
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                'subjects.code as subject_code',
                'admissions.current_class as class_level',
                'admissions.admission_year',
                'student_subject_sections.section as section',
                DB::raw('COUNT(DISTINCT student_teacher_subjects.student_id) as student_count')
            )
            ->groupBy(
                'student_teacher_subjects.staff_id',
                'subjects.id',
                'subjects.name',
                'subjects.code',
                'admissions.current_class',
                'admissions.admission_year',
                'student_subject_sections.section'
            )
            ->get()
            ->groupBy('staff_id');
    }

    /**
     * Fetch HE / BS teaching blocks (subject → program → semester breakdown per staff).
     */
    public function getHeTeachingBlocks(Collection $staffIds): Collection
    {
        return DB::table('he_teacher_assignments')
            ->join('bs_subjects', 'he_teacher_assignments.bs_subject_id', '=', 'bs_subjects.id')
            ->join('he_admissions', 'he_teacher_assignments.he_admission_id', '=', 'he_admissions.id')
            ->leftJoin('programs', 'he_admissions.program_id', '=', 'programs.id')
            ->whereIn('he_teacher_assignments.staff_id', $staffIds)
            ->whereIn('he_admissions.status', ['active', 'admitted', 'Active', 'Admitted'])
            ->whereNull('he_admissions.deleted_at')
            ->select(
                'he_teacher_assignments.staff_id',
                'bs_subjects.id as bs_subject_id',
                'bs_subjects.name as subject_name',
                'bs_subjects.code as subject_code',
                'programs.name as program_name',
                'he_admissions.current_semester as semester_no',
                DB::raw('COUNT(DISTINCT he_teacher_assignments.he_admission_id) as student_count')
            )
            ->groupBy(
                'he_teacher_assignments.staff_id',
                'bs_subjects.id',
                'bs_subjects.name',
                'bs_subjects.code',
                'programs.name',
                'he_admissions.current_semester'
            )
            ->get()
            ->groupBy('staff_id');
    }

    /**
     * Fetch timetable slot data keyed for quick O(1) lookup.
     *
     * @return array{Collection, Collection, Collection}  [hsscBlockSlots, hsscGenSlots, heGenSlots]
     */
    public function getTimetableSlots(Collection $staffIds): array
    {
        $hsscBlockSlots = DB::table('timetable_entries')
            ->join('time_slots', 'timetable_entries.time_slot_id', '=', 'time_slots.id')
            ->whereIn('timetable_entries.staff_id', $staffIds)
            ->where('timetable_entries.is_active', 1)
            ->select(
                'timetable_entries.staff_id',
                'timetable_entries.subject_id',
                'timetable_entries.admission_year',
                DB::raw("COALESCE(timetable_entries.section, '') as section"),
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.slot_number ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_numbers"),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.name ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_names")
            )
            ->groupBy('timetable_entries.staff_id', 'timetable_entries.subject_id', 'timetable_entries.admission_year', DB::raw("COALESCE(timetable_entries.section, '')"))
            ->get()
            ->keyBy(fn($item) => $item->staff_id . '_' . $item->subject_id . '_' . $item->admission_year . '_' . trim($item->section));

        $hsscGenSlots = DB::table('timetable_entries')
            ->join('time_slots', 'timetable_entries.time_slot_id', '=', 'time_slots.id')
            ->whereIn('timetable_entries.staff_id', $staffIds)
            ->where('timetable_entries.is_active', 1)
            ->select(
                'timetable_entries.staff_id',
                'timetable_entries.subject_id',
                'timetable_entries.admission_year',
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.slot_number ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_numbers"),
                DB::raw("GROUP_CONCAT(DISTINCT time_slots.name ORDER BY time_slots.slot_number ASC SEPARATOR ', ') as slot_names")
            )
            ->groupBy('timetable_entries.staff_id', 'timetable_entries.subject_id', 'timetable_entries.admission_year')
            ->get()
            ->keyBy(fn($item) => $item->staff_id . '_' . $item->subject_id . '_' . $item->admission_year);

        $heGenSlots = DB::table('he_timetables')
            ->whereIn('staff_id', $staffIds)
            ->select(
                'staff_id',
                'bs_subject_id',
                DB::raw('COUNT(*) as slots_count'),
                DB::raw("GROUP_CONCAT(DISTINCT period_number ORDER BY period_number ASC SEPARATOR ', ') as slot_numbers")
            )
            ->groupBy('staff_id', 'bs_subject_id')
            ->get()
            ->keyBy(fn($item) => $item->staff_id . '_' . $item->bs_subject_id);

        return [$hsscBlockSlots, $hsscGenSlots, $heGenSlots];
    }

    /**
     * Classify a teacher's workload tier based on weekly class count.
     */
    public function classifyWorkloadTier(int $weeklyClasses): string
    {
        if ($weeklyClasses >= 25) return 'high';
        if ($weeklyClasses >= 15) return 'moderate';
        if ($weeklyClasses > 0)  return 'light';
        return 'unassigned';
    }

    /**
     * Build the full workload report for each staff member.
     *
     * Combines all pre-fetched data into a ready-to-render collection.
     *
     * @return array{Collection, int, int, float}  [reportItems, totalScheduledClasses, totalStudentsTaughtSum, avgWeeklyClasses]
     */
    public function buildWorkloadReport(
        Collection $staffList,
        Collection $hsscPeriods,
        Collection $hePeriods,
        Collection $hsscStudents,
        Collection $heStudents,
        Collection $hsscBlocks,
        Collection $heBlocks,
        $hsscBlockSlots,
        $hsscGenSlots,
        $heGenSlots,
        string $tierFilter = 'all'
    ): array {
        $reportItems = collect();
        $totalScheduledClasses = 0;
        $totalStudentsTaughtSum = 0;

        foreach ($staffList as $stf) {
            $hsscWk = $hsscPeriods->get($stf->id, 0);
            $heWk = $hePeriods->get($stf->id, 0);
            $weeklyClasses = $hsscWk + $heWk;

            $hsscStdCount = $hsscStudents->has($stf->id)
                ? $hsscStudents[$stf->id]->pluck('student_id')->unique()->count()
                : 0;
            $heStdCount = $heStudents->has($stf->id)
                ? $heStudents[$stf->id]->pluck('he_admission_id')->unique()->count()
                : 0;
            $totalStudentsTaught = $hsscStdCount + $heStdCount;

            $assignedBlocks = collect();

            // HSSC blocks
            if ($hsscBlocks->has($stf->id)) {
                foreach ($hsscBlocks[$stf->id] as $hb) {
                    $sec = trim($hb->section ?? '');
                    $secStr = !empty($sec) ? "Sec {$sec}" : "All Secs";

                    $exactKey = $stf->id . '_' . $hb->subject_id . '_' . $hb->admission_year . '_' . $sec;
                    $genKey = $stf->id . '_' . $hb->subject_id . '_' . $hb->admission_year;

                    if (isset($hsscBlockSlots[$exactKey])) {
                        $slots = $hsscBlockSlots[$exactKey]->slots_count;
                        $slotNos = $hsscBlockSlots[$exactKey]->slot_numbers;
                        $slotNames = $hsscBlockSlots[$exactKey]->slot_names;
                    } elseif (isset($hsscGenSlots[$genKey])) {
                        $slots = $hsscGenSlots[$genKey]->slots_count;
                        $slotNos = $hsscGenSlots[$genKey]->slot_numbers;
                        $slotNames = $hsscGenSlots[$genKey]->slot_names;
                    } else {
                        $slots = 0;
                        $slotNos = '';
                        $slotNames = '';
                    }

                    $assignedBlocks->push((object) [
                        'type'          => 'HSSC',
                        'subject_id'    => $hb->subject_id,
                        'subject_name'  => $hb->subject_name,
                        'subject_code'  => $hb->subject_code,
                        'description'   => "{$hb->class_level}th Class ({$hb->admission_year}) - {$secStr}",
                        'student_count' => $hb->student_count,
                        'slots_count'   => $slots,
                        'slot_numbers'  => $slotNos,
                        'slot_names'    => $slotNames,
                    ]);
                }
            }

            // HE blocks
            if ($heBlocks->has($stf->id)) {
                foreach ($heBlocks[$stf->id] as $heb) {
                    $progStr = !empty($heb->program_name) ? $heb->program_name : "BS/ADP";
                    $heKey = $stf->id . '_' . $heb->bs_subject_id;
                    $slots = isset($heGenSlots[$heKey]) ? $heGenSlots[$heKey]->slots_count : 0;
                    $slotNos = isset($heGenSlots[$heKey]) ? $heGenSlots[$heKey]->slot_numbers : '';
                    $slotNames = !empty($slotNos) ? "Lecture {$slotNos}" : '';

                    $assignedBlocks->push((object) [
                        'type'          => 'BS/ADP',
                        'subject_id'    => $heb->bs_subject_id,
                        'subject_name'  => $heb->subject_name,
                        'subject_code'  => $heb->subject_code,
                        'description'   => "{$progStr} (Sem {$heb->semester_no})",
                        'student_count' => $heb->student_count,
                        'slots_count'   => $slots,
                        'slot_numbers'  => $slotNos,
                        'slot_names'    => $slotNames,
                    ]);
                }
            }

            $workloadTier = $this->classifyWorkloadTier($weeklyClasses);

            // Apply tier filter
            if ($tierFilter !== 'all' && $workloadTier !== $tierFilter) {
                continue;
            }

            $totalScheduledClasses += $weeklyClasses;
            $totalStudentsTaughtSum += $totalStudentsTaught;

            $teacherName = trim(($stf->first_name ?? '') . ' ' . ($stf->last_name ?? ''));

            $reportItems->push((object) [
                'staff_id'             => $stf->id,
                'employee_id'          => $stf->staff_id,
                'teacher_name'         => $teacherName,
                'designation'          => $stf->designation ?? 'N/A',
                'bps'                  => $stf->bps ?? null,
                'category'             => $stf->category ?? 'teaching',
                'weekly_classes'       => $weeklyClasses,
                'hssc_weekly_classes'  => $hsscWk,
                'he_weekly_classes'    => $heWk,
                'total_students_taught'=> $totalStudentsTaught,
                'hssc_students_count'  => $hsscStdCount,
                'he_students_count'    => $heStdCount,
                'assigned_blocks'      => $assignedBlocks,
                'subjects_count'       => $assignedBlocks->pluck('subject_name')->unique()->count(),
                'workload_tier'        => $workloadTier,
            ]);
        }

        $totalTeachersCount = $reportItems->count();
        $avgWeeklyClasses = $totalTeachersCount > 0
            ? round($totalScheduledClasses / $totalTeachersCount, 1)
            : 0;

        return [$reportItems, $totalScheduledClasses, $totalStudentsTaughtSum, $avgWeeklyClasses];
    }
}
