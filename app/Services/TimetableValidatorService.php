<?php

namespace App\Services;

use App\Models\TimetableEntry;
use App\Models\StudentTeacherSubject;

class TimetableValidatorService
{
    /**
     * Check if a teacher is available for a new timetable block.
     * Allows combined sections for the same teacher, subject, and admission year.
     */
    public function hasTeacherClash(int $staffId, string $dayOfWeek, int $timeSlotId, ?int $subjectId = null, ?string $admissionYear = null, ?int $ignoreEntryId = null): bool
    {
        return TimetableEntry::where('staff_id', $staffId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot_id', $timeSlotId)
            ->when($ignoreEntryId, function($q) use ($ignoreEntryId) {
                return $q->where('id', '!=', $ignoreEntryId);
            })
            ->when($subjectId && $admissionYear, function($q) use ($subjectId, $admissionYear) {
                // Same teacher teaching the same subject for the same batch/year is a combined class, not a clash.
                // A clash exists if the teacher is assigned a DIFFERENT subject or DIFFERENT admission year at the same slot.
                return $q->where(function($subQ) use ($subjectId, $admissionYear) {
                    $subQ->where('subject_id', '!=', $subjectId)
                         ->orWhere('admission_year', '!=', $admissionYear);
                });
            })
            ->exists();
    }

    /**
     * Check if a room is available for a new timetable block.
     * Ignores room clash if the same teacher and same admission year are assigned to that room.
     */
    public function hasRoomClash(int $roomId, string $dayOfWeek, int $timeSlotId, ?int $staffId = null, ?string $admissionYear = null, ?int $ignoreEntryId = null): bool
    {
        return TimetableEntry::where('room_id', $roomId)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot_id', $timeSlotId)
            ->when($ignoreEntryId, function($q) use ($ignoreEntryId) {
                return $q->where('id', '!=', $ignoreEntryId);
            })
            ->when($staffId && $admissionYear, function($q) use ($staffId, $admissionYear) {
                // Ignore room clash if the same teacher and same admission year are using the room.
                // A clash exists if a DIFFERENT teacher or DIFFERENT admission year is using the room.
                return $q->where(function($subQ) use ($staffId, $admissionYear) {
                    $subQ->where('staff_id', '!=', $staffId)
                         ->orWhere('admission_year', '!=', $admissionYear);
                });
            })
            ->exists();
    }

    /**
     * Check if a section (or array of sections) already has a timetable block assigned at the same day and time slot.
     *
     * @param string|array $section
     * @param string $admissionYear
     * @param string $dayOfWeek
     * @param int $timeSlotId
     * @param int|null $ignoreEntryId
     * @return bool
     */
    public function hasSectionClash($section, string $admissionYear, string $dayOfWeek, int $timeSlotId, ?int $ignoreEntryId = null): bool
    {
        return TimetableEntry::query()
            ->when(is_array($section), function($q) use ($section) {
                return $q->whereIn('section', $section);
            }, function($q) use ($section) {
                return $q->where('section', $section);
            })
            ->where('admission_year', $admissionYear)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot_id', $timeSlotId)
            ->when($ignoreEntryId, function($q) use ($ignoreEntryId) {
                return $q->where('id', '!=', $ignoreEntryId);
            })
            ->exists();
    }

    /**
     * Get details of the clashing entry for a section (or array of sections).
     *
     * @param string|array $section
     * @param string $admissionYear
     * @param string $dayOfWeek
     * @param int $timeSlotId
     * @param int|null $ignoreEntryId
     * @return TimetableEntry|null
     */
    public function getSectionClashDetails($section, string $admissionYear, string $dayOfWeek, int $timeSlotId, ?int $ignoreEntryId = null): ?TimetableEntry
    {
        return TimetableEntry::query()
            ->when(is_array($section), function($q) use ($section) {
                return $q->whereIn('section', $section);
            }, function($q) use ($section) {
                return $q->where('section', $section);
            })
            ->where('admission_year', $admissionYear)
            ->where('is_active', true)
            ->where('day_of_week', $dayOfWeek)
            ->where('time_slot_id', $timeSlotId)
            ->when($ignoreEntryId, function($q) use ($ignoreEntryId) {
                return $q->where('id', '!=', $ignoreEntryId);
            })
            ->with(['staff', 'subject'])
            ->first();
    }


    /**
     * Check if assigning a student to a specific attendance section causes a clash.
     * Use this when Teachers are assigning students to Attendance Sections.
     */
    public function validateStudentSectionAssignment(int $studentId, int $targetSubjectId, int $targetStaffId, string $targetSection, string $admissionYear): array
    {
        // 1. Get all the timetable entries for the target section for this batch year
        $targetBlocks = TimetableEntry::where('subject_id', $targetSubjectId)
            ->where('is_active', true)
            ->where('staff_id', $targetStaffId)
            ->where('section', $targetSection)
            ->where('admission_year', $admissionYear)
            ->get();

        if ($targetBlocks->isEmpty()) {
            return ['isValid' => true];
        }

        // 2. Retrieve all OTHER specific allocations the student already belongs to
        $existingAllocations = \App\Models\StudentSubjectSection::where('student_id', $studentId)
            ->where('subject_id', '!=', $targetSubjectId)
            ->whereNotNull('section')
            ->select('subject_id', 'section')
            ->get();

        if ($existingAllocations->isEmpty()) {
            return ['isValid' => true];
        }

        // 3. For each target block period, check if any existing allocation is also scheduled at that time for the SAME batch year
        foreach ($targetBlocks as $targetBlock) {
            
            $clashingBlock = TimetableEntry::where(function($query) use ($existingAllocations) {
                foreach ($existingAllocations as $allocation) {
                    $query->orWhere(function($subQuery) use ($allocation) {
                        $subQuery->where('subject_id', $allocation->subject_id)
                                 ->where('section', $allocation->section);
                    });
                }
            })
            ->where('is_active', true)
            ->where('admission_year', $admissionYear) // MUST look at the same batch's schedule
            ->where('day_of_week', $targetBlock->day_of_week)
            ->where('time_slot_id', $targetBlock->time_slot_id)
            ->with(['subject', 'timeSlot']) 
            ->first();

            if ($clashingBlock) {
                return [
                    'isValid' => false,
                    'clash' => "Clash: Student has {$clashingBlock->subject->name} at {$clashingBlock->timeSlot->name} on {$clashingBlock->day_of_week} ({$clashingBlock->section})"
                ];
            }
        }

        return ['isValid' => true];
    }
}
