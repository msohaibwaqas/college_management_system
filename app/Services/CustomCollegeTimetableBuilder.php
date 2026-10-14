<?php

namespace App\Services;

use App\Models\TimetableEntry;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class CustomCollegeTimetableBuilder
{
    /**
     * Store a combined or single section timetable block for one or all days of the week.
     *
     * @param array $data
     * @param TimetableValidatorService $validator
     * @return array ['success' => bool, 'message' => string]
     */
    public function storeCombinedBlock(array $data, TimetableValidatorService $validator): array
    {
        $subjectId = $data['subject_id'];
        $staffId = $data['staff_id'];
        $admissionYear = $data['admission_year'];
        $timeSlotId = $data['time_slot_id'];
        $roomId = $data['room_id'] ?? null;
        $dayInput = $data['day_of_week'];

        // Normalize sections to array
        $rawSections = $data['sections'] ?? $data['section'] ?? [];
        $sections = is_array($rawSections) ? $rawSections : [$rawSections];
        $sections = array_filter(array_map('trim', $sections));

        if (empty($sections)) {
            return ['success' => false, 'message' => 'Please select at least one section.'];
        }

        $daysToSchedule = $dayInput === 'All Week'
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$dayInput];

        $teacherClashes = [];
        $roomClashes = [];
        $sectionClashes = [];

        foreach ($daysToSchedule as $day) {
            // Check teacher availability
            if ($validator->hasTeacherClash($staffId, $day, $timeSlotId, $subjectId, $admissionYear)) {
                $teacherClashes[] = $day;
            }

            // Check room availability
            if ($roomId && $validator->hasRoomClash($roomId, $day, $timeSlotId, $staffId, $admissionYear)) {
                $roomClashes[] = $day;
            }

            // Check section clashes for all sections in group
            foreach ($sections as $section) {
                if ($validator->hasSectionClash($section, $admissionYear, $day, $timeSlotId)) {
                    $sectionClashes[] = "Section '{$section}' on {$day}";
                }
            }
        }

        if (count($teacherClashes) > 0 || count($roomClashes) > 0 || count($sectionClashes) > 0) {
            $msg = '';
            if (count($teacherClashes) > 0) {
                $msg .= 'Teacher Clash on: ' . implode(', ', array_unique($teacherClashes)) . '. ';
            }
            if (count($roomClashes) > 0) {
                $msg .= 'Room Clash on: ' . implode(', ', array_unique($roomClashes)) . '. ';
            }
            if (count($sectionClashes) > 0) {
                $msg .= 'Section Clash(es): ' . implode('; ', array_unique($sectionClashes)) . '. ';
            }
            return ['success' => false, 'message' => trim($msg)];
        }

        // Atomically create entries for each day and each section
        DB::transaction(function () use ($daysToSchedule, $sections, $subjectId, $staffId, $admissionYear, $timeSlotId, $roomId) {
            foreach ($daysToSchedule as $day) {
                foreach ($sections as $section) {
                    TimetableEntry::updateOrCreate(
                        [
                            'admission_year' => $admissionYear,
                            'section' => $section,
                            'day_of_week' => $day,
                            'time_slot_id' => $timeSlotId,
                        ],
                        [
                            'subject_id' => $subjectId,
                            'staff_id' => $staffId,
                            'room_id' => $roomId,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });

        $count = count($daysToSchedule) * count($sections);
        return [
            'success' => true,
            'message' => "Successfully scheduled {$count} lecture block(s) across " . count($sections) . " section(s)."
        ];
    }

    /**
     * Check availability of all time slots and days for a given teacher, room, and section list.
     * Useful for visual matrix rendering or AJAX slot availability checking.
     */
    public function checkSlotAvailability(int $staffId, ?int $roomId, array $sections, string $admissionYear, TimetableValidatorService $validator): array
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $matrix = [];

        foreach ($timeSlots as $slot) {
            foreach ($daysOfWeek as $day) {
                $teacherClash = $validator->hasTeacherClash($staffId, $day, $slot->id, null, $admissionYear);
                $roomClash = $roomId ? $validator->hasRoomClash($roomId, $day, $slot->id, $staffId, $admissionYear) : false;

                $sectionClash = false;
                $clashingSectionName = null;
                foreach ($sections as $sec) {
                    if ($validator->hasSectionClash($sec, $admissionYear, $day, $slot->id)) {
                        $sectionClash = true;
                        $clashingSectionName = $sec;
                        break;
                    }
                }

                $matrix[$slot->id][$day] = [
                    'time_slot_id' => $slot->id,
                    'day' => $day,
                    'is_available' => !$teacherClash && !$roomClash && !$sectionClash,
                    'teacher_clash' => $teacherClash,
                    'room_clash' => $roomClash,
                    'section_clash' => $sectionClash,
                    'clashing_section' => $clashingSectionName
                ];
            }
        }

        return $matrix;
    }
}
