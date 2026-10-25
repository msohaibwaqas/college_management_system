<?php

namespace App\Services;

use App\Models\Admissions;
use App\Models\BsSubject;
use App\Models\HeAdmission;
use App\Models\HeTimetable;
use App\Models\Program;
use App\Models\Room;
use App\Models\Semester;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectSection;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TimetableService
{
    // ─── Section & Grid Resolution ───────────────────────────────────

    /**
     * Get available distinct sections for an admission year and optional subject.
     */
    public function getAvailableSectionsForYear(?string $admissionYear, ?int $subjectId = null): Collection
    {
        if (!$admissionYear) {
            return collect(['M1', 'M2', 'E1', 'ICS-I', 'ICS-II', 'ICS-III', 'ICS-IV', 'FA-IT']);
        }

        $subjectSections = StudentSubjectSection::where('admission_year', $admissionYear)
            ->when($subjectId, function ($q) use ($subjectId) {
                return $q->where('subject_id', $subjectId);
            })
            ->distinct()
            ->pluck('section');

        $timetableSections = TimetableEntry::where('admission_year', $admissionYear)
            ->when($subjectId, function ($q) use ($subjectId) {
                return $q->where('subject_id', $subjectId);
            })
            ->distinct()
            ->pluck('section');

        $sections = $subjectSections->merge($timetableSections)
            ->filter()
            ->reject(function ($s) {
                return str_contains($s, '+');
            })
            ->unique()
            ->sort()
            ->values();

        if ($sections->isEmpty() && $subjectId) {
            $sections = StudentSubjectSection::where('admission_year', $admissionYear)
                ->distinct()
                ->pluck('section')
                ->unique()
                ->sort()
                ->values();
        }

        if ($sections->isEmpty()) {
            $sections = collect(['M1', 'M2', 'E1', 'ICS-I', 'ICS-II', 'ICS-III', 'ICS-IV', 'FA-IT']);
        }

        return $sections;
    }

    /**
     * Build weekly matrix grid indexed by time_slot_id and day_of_week.
     */
    public function buildMatrixGrid(Collection $entries): array
    {
        $grid = [];
        foreach ($entries as $entry) {
            $grid[$entry->time_slot_id][$entry->day_of_week][] = $entry;
        }
        return $grid;
    }

    // ─── Single & Weekly Block Management ────────────────────────────

    /**
     * Store a single or weekly timetable block with clash validation.
     *
     * @param array $data
     * @param TimetableValidatorService $validator
     * @return array{success: bool, error?: string, days_count: int}
     */
    public function storeEntryBlock(array $data, TimetableValidatorService $validator): array
    {
        $daysToSchedule = $data['day_of_week'] === 'All Week'
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$data['day_of_week']];

        $teacherClashes = [];
        $roomClashes = [];
        $sectionClashes = [];

        foreach ($daysToSchedule as $day) {
            if ($validator->hasTeacherClash($data['staff_id'], $day, $data['time_slot_id'], $data['subject_id'], $data['admission_year'])) {
                $teacherClashes[] = $day;
            }
            if (!empty($data['room_id']) && $validator->hasRoomClash($data['room_id'], $day, $data['time_slot_id'], $data['staff_id'], $data['admission_year'])) {
                $roomClashes[] = $day;
            }
            if ($validator->hasSectionClash($data['section'], $data['admission_year'], $day, $data['time_slot_id'])) {
                $sectionClashes[] = $day;
            }
        }

        if (count($teacherClashes) > 0 || count($roomClashes) > 0 || count($sectionClashes) > 0) {
            $msg = '';
            if (count($teacherClashes) > 0) $msg .= 'Teacher Clash on: ' . implode(', ', $teacherClashes) . '. ';
            if (count($roomClashes) > 0) $msg .= 'Room Clash on: ' . implode(', ', $roomClashes) . '. ';
            if (count($sectionClashes) > 0) {
                $clashDetail = $validator->getSectionClashDetails($data['section'], $data['admission_year'], $sectionClashes[0], $data['time_slot_id']);
                $occupantInfo = $clashDetail ? ' (Currently assigned to ' . ($clashDetail->staff->full_name ?? 'another teacher') . ' for ' . ($clashDetail->subject->name ?? 'another subject') . ')' : '';
                $msg .= 'Section Clash: Section "' . $data['section'] . '" is already scheduled on ' . implode(', ', $sectionClashes) . $occupantInfo . '. Please delete or edit the existing entry first if you wish to reassign this section.';
            }
            return ['success' => false, 'error' => trim($msg), 'days_count' => 0];
        }

        foreach ($daysToSchedule as $day) {
            $row = $data;
            $row['day_of_week'] = $day;
            TimetableEntry::create($row);
        }

        return ['success' => true, 'days_count' => count($daysToSchedule)];
    }

    /**
     * Update a timetable block (single day or across all week) with clash checks.
     *
     * @param TimetableEntry $entry
     * @param array $data
     * @param TimetableValidatorService $validator
     * @return array{success: bool, error?: string, days_count: int}
     */
    public function updateEntryBlock(TimetableEntry $entry, array $data, TimetableValidatorService $validator): array
    {
        $daysToSchedule = $data['day_of_week'] === 'All Week'
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$data['day_of_week']];

        $teacherClashes = [];
        $roomClashes = [];
        $sectionClashes = [];

        foreach ($daysToSchedule as $day) {
            $ignoreId = ($day === $entry->day_of_week) ? $entry->id : null;

            if (!$ignoreId && $data['day_of_week'] === 'All Week') {
                $existingDayEntry = TimetableEntry::where('admission_year', $entry->admission_year)
                    ->where('section', $entry->section)
                    ->where('time_slot_id', $entry->time_slot_id)
                    ->where('day_of_week', $day)
                    ->first();
                if ($existingDayEntry) {
                    $ignoreId = $existingDayEntry->id;
                }
            }

            if ($validator->hasTeacherClash($data['staff_id'], $day, $data['time_slot_id'], $data['subject_id'], $data['admission_year'], $ignoreId)) {
                $teacherClashes[] = $day;
            }
            if (!empty($data['room_id']) && $validator->hasRoomClash($data['room_id'], $day, $data['time_slot_id'], $data['staff_id'], $data['admission_year'], $ignoreId)) {
                $roomClashes[] = $day;
            }
            if ($validator->hasSectionClash($data['section'], $data['admission_year'], $day, $data['time_slot_id'], $ignoreId)) {
                $sectionClashes[] = $day;
            }
        }

        if (count($teacherClashes) > 0 || count($roomClashes) > 0 || count($sectionClashes) > 0) {
            $msg = '';
            if (count($teacherClashes) > 0) $msg .= 'Teacher Clash on: ' . implode(', ', $teacherClashes) . '. ';
            if (count($roomClashes) > 0) $msg .= 'Room Clash on: ' . implode(', ', $roomClashes) . '. ';
            if (count($sectionClashes) > 0) {
                $firstClashDay = $sectionClashes[0];
                $ignoreIdForDetail = ($firstClashDay === $entry->day_of_week) ? $entry->id : null;
                $clashDetail = $validator->getSectionClashDetails($data['section'], $data['admission_year'], $firstClashDay, $data['time_slot_id'], $ignoreIdForDetail);
                $occupantInfo = $clashDetail ? ' (Currently assigned to ' . ($clashDetail->staff->full_name ?? 'another teacher') . ' for ' . ($clashDetail->subject->name ?? 'another subject') . ')' : '';
                $msg .= 'Section Clash: Section "' . $data['section'] . '" is already scheduled on ' . implode(', ', $sectionClashes) . $occupantInfo . '. ';
            }
            return ['success' => false, 'error' => trim($msg), 'days_count' => 0];
        }

        if ($data['day_of_week'] === 'All Week') {
            foreach ($daysToSchedule as $day) {
                TimetableEntry::updateOrCreate(
                    [
                        'admission_year' => $data['admission_year'],
                        'section' => $data['section'],
                        'day_of_week' => $day,
                        'time_slot_id' => $data['time_slot_id'],
                    ],
                    [
                        'subject_id' => $data['subject_id'],
                        'staff_id' => $data['staff_id'],
                        'room_id' => $data['room_id'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        } else {
            $entry->update([
                'admission_year' => $data['admission_year'],
                'day_of_week' => $data['day_of_week'],
                'time_slot_id' => $data['time_slot_id'],
                'subject_id' => $data['subject_id'],
                'staff_id' => $data['staff_id'],
                'room_id' => $data['room_id'] ?? null,
                'section' => $data['section'],
            ]);
        }

        return ['success' => true, 'days_count' => count($daysToSchedule)];
    }

    /**
     * Delete all matching entries across the week for a slot/teacher/subject/year/section.
     */
    public function bulkDestroyEntry(TimetableEntry $entry): int
    {
        return TimetableEntry::where('subject_id', $entry->subject_id)
            ->where('staff_id', $entry->staff_id)
            ->where('section', $entry->section)
            ->where('admission_year', $entry->admission_year)
            ->where('time_slot_id', $entry->time_slot_id)
            ->delete();
    }

    /**
     * Clone all active timetable entries from one year to another.
     */
    public function cloneTimetableYear(string $sourceYear, string $targetYear): array
    {
        $sourceEntries = TimetableEntry::where('admission_year', $sourceYear)->get();

        if ($sourceEntries->isEmpty()) {
            return [
                'success' => false,
                'count' => 0,
                'error' => "No entries found for admission year {$sourceYear} to clone.",
            ];
        }

        foreach ($sourceEntries as $entry) {
            $newEntry = $entry->replicate();
            $newEntry->admission_year = $targetYear;
            $newEntry->is_active = true;
            $newEntry->save();
        }

        return [
            'success' => true,
            'count' => $sourceEntries->count(),
        ];
    }

    // ─── Batch Scheduling (Teacher & Subject) ────────────────────────

    /**
     * Process and persist batch lecture slot assignments for a teacher.
     */
    public function storeTeacherBatch(
        string $admissionYear,
        int $subjectId,
        int $staffId,
        array $assignments,
        TimetableValidatorService $validator
    ): array {
        $activeSlots = [];
        $clashes = [];

        foreach ($assignments as $slotId => $slotData) {
            if (empty($slotData['enabled'])) {
                continue;
            }

            $rawSections = $slotData['sections'] ?? [];
            $sections = is_array($rawSections) ? array_filter(array_map('trim', $rawSections)) : [];

            if (empty($sections)) {
                $clashes[] = "Lecture slot {$slotId}: Please select at least one section.";
                continue;
            }

            $dayInput = $slotData['day_of_week'] ?? 'All Week';
            $roomId = !empty($slotData['room_id']) ? (int)$slotData['room_id'] : null;

            $daysToSchedule = ($dayInput === 'All Week')
                ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
                : [$dayInput];

            $activeSlots[$slotId] = [
                'slot_id' => (int)$slotId,
                'sections' => $sections,
                'room_id' => $roomId,
                'day_input' => $dayInput,
                'days' => $daysToSchedule,
            ];

            foreach ($daysToSchedule as $day) {
                if ($validator->hasTeacherClash($staffId, $day, (int)$slotId, $subjectId, $admissionYear)) {
                    $clashes[] = "Teacher Clash on {$day} for Lecture Slot ID {$slotId}.";
                }

                if ($roomId && $validator->hasRoomClash($roomId, $day, (int)$slotId, $staffId, $admissionYear)) {
                    $clashes[] = "Room Clash on {$day} for Lecture Slot ID {$slotId}.";
                }

                foreach ($sections as $section) {
                    if ($validator->hasSectionClash($section, $admissionYear, $day, (int)$slotId)) {
                        $clashes[] = "Section Clash: '{$section}' already scheduled on {$day} (Slot {$slotId}).";
                    }
                }
            }
        }

        if (empty($activeSlots)) {
            return [
                'success' => false,
                'error' => 'Please check at least one lecture slot to schedule.',
                'created_count' => 0,
                'active_slots_count' => 0,
            ];
        }

        if (count($clashes) > 0) {
            return [
                'success' => false,
                'error' => implode(' ', array_unique($clashes)),
                'created_count' => 0,
                'active_slots_count' => count($activeSlots),
            ];
        }

        $createdCount = 0;
        DB::transaction(function () use ($activeSlots, $admissionYear, $subjectId, $staffId, &$createdCount) {
            foreach ($activeSlots as $slot) {
                foreach ($slot['days'] as $day) {
                    foreach ($slot['sections'] as $section) {
                        TimetableEntry::updateOrCreate(
                            [
                                'admission_year' => $admissionYear,
                                'section' => $section,
                                'day_of_week' => $day,
                                'time_slot_id' => $slot['slot_id'],
                            ],
                            [
                                'subject_id' => $subjectId,
                                'staff_id' => $staffId,
                                'room_id' => $slot['room_id'],
                                'is_active' => true,
                            ]
                        );
                        $createdCount++;
                    }
                }
            }
        });

        return [
            'success' => true,
            'created_count' => $createdCount,
            'active_slots_count' => count($activeSlots),
        ];
    }

    /**
     * Retrieve subject teachers, allocations, and teacher sections mapping.
     */
    public function getSubjectBatchData(int $subjectId, string $admissionYear): array
    {
        $subjectTeachers = Staff::where('category', 'teaching')
            ->where('status', 'active')
            ->where(function ($q) use ($subjectId) {
                $q->whereHas('subjectSpecializations', function ($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                })->orWhereHas('studentTeacherSubjects', function ($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                });
            })
            ->orderBy('first_name', 'asc')
            ->get();

        if ($subjectTeachers->isEmpty()) {
            $subjectTeachers = Staff::where('category', 'teaching')
                ->where('status', 'active')
                ->orderBy('first_name', 'asc')
                ->get();
        }

        $entries = TimetableEntry::with(['staff', 'room'])
            ->where('subject_id', $subjectId)
            ->where('admission_year', $admissionYear)
            ->where('is_active', true)
            ->get();

        $grouped = [];
        foreach ($entries as $entry) {
            $groupKey = $entry->staff_id . '_' . ($entry->room_id ?? 'noroom') . '_' . $entry->day_of_week;
            if (!isset($grouped[$entry->time_slot_id][$groupKey])) {
                $grouped[$entry->time_slot_id][$groupKey] = [
                    'staff_id' => $entry->staff_id,
                    'room_id' => $entry->room_id,
                    'day_of_week' => $entry->day_of_week,
                    'sections' => [],
                ];
            }
            $grouped[$entry->time_slot_id][$groupKey]['sections'][] = $entry->section;
        }

        $existingSubjectAllocations = [];
        foreach ($grouped as $slotId => $groupList) {
            foreach ($groupList as $g) {
                $g['sections'] = array_values(array_unique($g['sections']));
                $existingSubjectAllocations[$slotId][] = $g;
            }
        }

        $teacherSectionsMap = [];
        $stsAssignments = \App\Models\StudentTeacherSubject::with(['student.subjectSections' => function ($q) use ($subjectId, $admissionYear) {
            $q->where('subject_id', $subjectId)->where('admission_year', $admissionYear);
        }])
        ->where('subject_id', $subjectId)
        ->whereHas('student.admissions', function ($q) use ($admissionYear) {
            $q->where('admission_year', $admissionYear);
        })
        ->get();

        foreach ($stsAssignments as $assignment) {
            if ($assignment->student && $assignment->student->subjectSections) {
                foreach ($assignment->student->subjectSections as $ss) {
                    if ($ss->section && !str_contains($ss->section, '+')) {
                        $teacherSectionsMap[$assignment->staff_id][] = trim($ss->section);
                    }
                }
            }
            if ($assignment->attendance_section && !str_contains($assignment->attendance_section, '+')) {
                $teacherSectionsMap[$assignment->staff_id][] = trim($assignment->attendance_section);
            }
        }

        foreach ($entries as $tte) {
            if ($tte->staff_id && $tte->section && !str_contains($tte->section, '+')) {
                $teacherSectionsMap[$tte->staff_id][] = trim($tte->section);
            }
        }

        foreach ($teacherSectionsMap as $sId => $secList) {
            $teacherSectionsMap[$sId] = array_values(array_unique(array_filter($secList)));
        }

        return [
            'subject_teachers' => $subjectTeachers,
            'existing_allocations' => $existingSubjectAllocations,
            'teacher_sections_map' => $teacherSectionsMap,
        ];
    }

    /**
     * Store parallel subject class allocations atomically for a department.
     */
    public function storeSubjectBatch(
        string $admissionYear,
        int $subjectId,
        array $assignments,
        TimetableValidatorService $validator
    ): array {
        $parsedRows = [];
        $clashes = [];

        foreach ($assignments as $slotId => $rows) {
            if (!is_array($rows)) continue;

            foreach ($rows as $rowIndex => $row) {
                if (empty($row['staff_id'])) continue;

                $staffId = (int)$row['staff_id'];
                $rawSections = $row['sections'] ?? [];
                $sections = is_array($rawSections) ? array_filter(array_map('trim', $rawSections)) : [];

                if (empty($sections)) {
                    $clashes[] = "Lecture Slot {$slotId} (Row " . ($rowIndex + 1) . "): Please select at least one section.";
                    continue;
                }

                $dayInput = $row['day_of_week'] ?? 'All Week';
                $roomId = !empty($row['room_id']) ? (int)$row['room_id'] : null;

                $daysToSchedule = ($dayInput === 'All Week')
                    ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
                    : [$dayInput];

                $parsedRows[] = [
                    'slot_id' => (int)$slotId,
                    'staff_id' => $staffId,
                    'sections' => $sections,
                    'room_id' => $roomId,
                    'day_input' => $dayInput,
                    'days' => $daysToSchedule,
                ];

                foreach ($daysToSchedule as $day) {
                    if ($validator->hasTeacherClash($staffId, $day, (int)$slotId, $subjectId, $admissionYear)) {
                        $teacherName = Staff::find($staffId)->full_name ?? "Teacher ID {$staffId}";
                        $clashes[] = "Teacher Clash: {$teacherName} is busy on {$day} during Lecture Slot {$slotId}.";
                    }

                    if ($roomId && $validator->hasRoomClash($roomId, $day, (int)$slotId, $staffId, $admissionYear)) {
                        $roomName = Room::find($roomId)->name ?? "Room ID {$roomId}";
                        $clashes[] = "Room Clash: {$roomName} is occupied on {$day} during Lecture Slot {$slotId}.";
                    }

                    foreach ($sections as $section) {
                        if ($validator->hasSectionClash($section, $admissionYear, $day, (int)$slotId)) {
                            $clashes[] = "Section Clash: Section '{$section}' is already scheduled on {$day} during Lecture Slot {$slotId}.";
                        }
                    }
                }
            }
        }

        if (count($clashes) > 0) {
            return [
                'success' => false,
                'error' => implode(' ', array_unique($clashes)),
                'created_count' => 0,
                'parsed_rows_count' => count($parsedRows),
            ];
        }

        $createdCount = 0;
        DB::transaction(function () use ($parsedRows, $admissionYear, $subjectId, &$createdCount) {
            TimetableEntry::where('subject_id', $subjectId)
                ->where('admission_year', $admissionYear)
                ->delete();

            foreach ($parsedRows as $row) {
                foreach ($row['days'] as $day) {
                    foreach ($row['sections'] as $section) {
                        TimetableEntry::create([
                            'admission_year' => $admissionYear,
                            'subject_id' => $subjectId,
                            'staff_id' => $row['staff_id'],
                            'section' => $section,
                            'day_of_week' => $day,
                            'time_slot_id' => $row['slot_id'],
                            'room_id' => $row['room_id'],
                            'is_active' => true,
                        ]);
                        $createdCount++;
                    }
                }
            }
        });

        return [
            'success' => true,
            'created_count' => $createdCount,
            'parsed_rows_count' => count($parsedRows),
        ];
    }

    // ─── Higher Education (HE) Timetable Operations ──────────────────

    /**
     * Check conflicts for Higher Education timetables (cross-checking HE and HSSC rooms/staff).
     */
    public function checkHeConflicts(
        int $programId,
        int $semester,
        int $admissionYear,
        string $day,
        int $period,
        int $roomId,
        ?int $staffId = null
    ): array {
        $conflicts = [];

        // 1. Room conflict — Global across ALL HE batches and HSSC
        $roomConflictHE = HeTimetable::where('day_of_week', $day)
            ->where('period_number', $period)
            ->where('room_id', $roomId)
            ->exists();

        $roomConflictHSSC = Timetable::where('day_of_week', $day)
            ->where('period_number', $period)
            ->where('room_id', $roomId)
            ->exists();

        if ($roomConflictHE || $roomConflictHSSC) {
            $conflicts[] = "Room is already occupied in this slot.";
        }

        // 2. Staff conflict — Global across ALL HE batches and HSSC
        if ($staffId) {
            $staffConflictHE = HeTimetable::where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('staff_id', $staffId)
                ->exists();

            $staffConflictHSSC = Timetable::where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('staff_id', $staffId)
                ->exists();

            if ($staffConflictHE || $staffConflictHSSC) {
                $conflicts[] = "Staff member is already assigned in this slot.";
            }
        }

        // 3. Program/Semester/Batch slot conflict — within same batch only
        $slotConflict = HeTimetable::where('program_id', $programId)
            ->where('semester_no', $semester)
            ->where('admission_year', $admissionYear)
            ->where('day_of_week', $day)
            ->where('period_number', $period)
            ->exists();

        if ($slotConflict) {
            $conflicts[] = "A subject is already scheduled for this program/semester/batch in this slot.";
        }

        return $conflicts;
    }

    /**
     * Store Higher Education timetable entries across specified days.
     */
    public function storeHeTimetable(array $data, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();
        $timeSlot = TimeSlot::where('slot_number', $data['period_number'])->firstOrFail();

        $daysToSchedule = match ($data['day_of_week']) {
            'All Week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'First Three Days' => ['Monday', 'Tuesday', 'Wednesday'],
            'Last Three Days' => ['Thursday', 'Friday', 'Saturday'],
            default => [$data['day_of_week']],
        };

        $allConflicts = [];
        $created = 0;

        foreach ($daysToSchedule as $day) {
            $conflicts = $this->checkHeConflicts(
                (int)$data['program_id'],
                (int)$data['semester_no'],
                (int)$data['admission_year'],
                $day,
                (int)$data['period_number'],
                (int)$data['room_id'],
                !empty($data['staff_id']) ? (int)$data['staff_id'] : null
            );

            if (!empty($conflicts)) {
                $allConflicts[] = "{$day}: " . implode(', ', $conflicts);
                continue;
            }

            HeTimetable::create([
                'program_id' => $data['program_id'],
                'semester_no' => $data['semester_no'],
                'admission_year' => $data['admission_year'],
                'day_of_week' => $day,
                'period_number' => $data['period_number'],
                'bs_subject_id' => $data['bs_subject_id'],
                'room_id' => $data['room_id'],
                'staff_id' => $data['staff_id'] ?? null,
                'start_time' => $timeSlot->start_time,
                'end_time' => $timeSlot->end_time,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
            $created++;
        }

        return [
            'success' => $created > 0,
            'created' => $created,
            'conflicts' => $allConflicts,
        ];
    }

    /**
     * Clone an HE timetable from source program/semester/year to target.
     */
    public function cloneHeTimetable(
        int $sourceProgramId,
        int $sourceSemester,
        int $sourceYear,
        int $targetProgramId,
        int $targetSemester,
        int $targetYear,
        ?int $userId = null
    ): array {
        $userId = $userId ?? Auth::id();

        $sourceEntries = HeTimetable::where('program_id', $sourceProgramId)
            ->where('semester_no', $sourceSemester)
            ->where('admission_year', $sourceYear)
            ->get();

        if ($sourceEntries->isEmpty()) {
            return [
                'success' => false,
                'cloned' => 0,
                'conflicts' => [],
                'error' => 'No timetable entries found in the source to clone.',
            ];
        }

        $targetExists = HeTimetable::where('program_id', $targetProgramId)
            ->where('semester_no', $targetSemester)
            ->where('admission_year', $targetYear)
            ->exists();

        if ($targetExists) {
            return [
                'success' => false,
                'cloned' => 0,
                'conflicts' => [],
                'error' => 'Target already has timetable entries. Please delete them first or choose a different target.',
            ];
        }

        $cloned = 0;
        $conflicts = [];

        DB::transaction(function () use ($sourceEntries, $targetProgramId, $targetSemester, $targetYear, $userId, &$cloned, &$conflicts) {
            foreach ($sourceEntries as $entry) {
                $entryConflicts = $this->checkHeConflicts(
                    $targetProgramId,
                    $targetSemester,
                    $targetYear,
                    $entry->day_of_week,
                    $entry->period_number,
                    $entry->room_id,
                    $entry->staff_id
                );

                if (!empty($entryConflicts)) {
                    $subjectName = $entry->subject->name ?? 'Unknown';
                    $conflicts[] = "{$subjectName} ({$entry->day_of_week} P{$entry->period_number}): " . implode(', ', $entryConflicts);
                    continue;
                }

                $newEntry = $entry->replicate();
                $newEntry->program_id = $targetProgramId;
                $newEntry->semester_no = $targetSemester;
                $newEntry->admission_year = $targetYear;
                $newEntry->created_by = $userId;
                $newEntry->updated_by = $userId;
                $newEntry->save();
                $cloned++;
            }
        });

        return [
            'success' => true,
            'cloned' => $cloned,
            'conflicts' => $conflicts,
        ];
    }
// ─── Legacy Subject-Group Timetable Operations ─────────────────────

    /**
     * Get index data for legacy subject-group timetables.
     */
    public function getLegacyIndexData(?string $admissionYear, string $selectedDay = 'Monday'): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $groups = \App\Models\SubjectGroup::orderBy('name')->get();
        $days = Timetable::getDaysOfWeek();
        $periods = Timetable::getPeriodTimes();

        $timetables = collect();
        $selectedYear = null;

        if ($admissionYear) {
            $selectedYear = $admissionYear;
            $timetables = Timetable::with(['subject', 'room', 'staff', 'group'])
                ->where('admission_year', $selectedYear)
                ->where('day_of_week', $selectedDay)
                ->orderBy('period_number')
                ->get();
        }

        return compact(
            'admissionYears',
            'timetables',
            'selectedYear',
            'groups',
            'days',
            'selectedDay',
            'periods'
        );
    }

    /**
     * Get create form data for legacy subject-group timetables.
     */
    public function getLegacyCreateData(): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $subjects = Subject::orderBy('name')->get();
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();
        $staff = Staff::orderBy('first_name')->get();
        $days = Timetable::getDaysOfWeek();
        $periods = Timetable::getPeriodTimes();
        $groups = \App\Models\SubjectGroup::orderBy('name')->get();

        return compact('admissionYears', 'subjects', 'rooms', 'staff', 'days', 'periods', 'groups');
    }

    /**
     * Check conflicts for legacy timetable entries.
     */
    public function checkLegacyConflicts($year, $groupId, $day, $period, $subjectId, $roomId, $staffId): array
    {
        $conflicts = [];

        if ($roomId) {
            $roomConflict = Timetable::where('admission_year', $year)
                ->where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('room_id', $roomId)
                ->exists();

            if ($roomConflict) {
                $conflicts[] = "Room is already occupied in this slot.";
            }
        }

        if ($staffId) {
            $staffConflict = Timetable::where('day_of_week', $day)
                ->where('period_number', $period)
                ->where('staff_id', $staffId)
                ->exists();

            if ($staffConflict) {
                $conflicts[] = "Staff member is already assigned in this slot.";
            }
        }

        $slotConflict = Timetable::where('admission_year', $year)
            ->where('subject_group_id', $groupId)
            ->where('day_of_week', $day)
            ->where('period_number', $period)
            ->where('subject_id', $subjectId)
            ->exists();

        if ($slotConflict) {
            $conflicts[] = "This subject is already scheduled for this group in this slot.";
        }

        return $conflicts;
    }

    /**
     * Store a single legacy timetable entry.
     */
    public function storeLegacyEntry(array $data, ?int $userId = null): Timetable
    {
        $periodTimes = Timetable::getPeriodTimes();
        $times = $periodTimes[$data['period_number']];
        $isBreak = (bool)($data['is_break'] ?? false);

        return Timetable::create([
            'admission_year' => $data['admission_year'],
            'subject_group_id' => $data['subject_group_id'],
            'day_of_week' => $data['day_of_week'],
            'period_number' => $data['period_number'],
            'subject_id' => $isBreak ? null : ($data['subject_id'] ?? null),
            'room_id' => $isBreak ? null : ($data['room_id'] ?? null),
            'staff_id' => $isBreak ? null : ($data['staff_id'] ?? null),
            'is_break' => $isBreak,
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * Get generate form data for legacy timetables.
     */
    public function getLegacyGenerateFormData(): array
    {
        $admissionYears = Admissions::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $year11 = Admissions::where('current_class', '11')->where('status', 'Active')->value('admission_year');
        $year12 = Admissions::where('current_class', '12')->where('status', 'Active')->value('admission_year');

        return compact('admissionYears', 'year11', 'year12');
    }

    /**
     * Automatically generate legacy timetable for year 11 and 12.
     */
    public function generateAutomaticLegacyTimetables(?int $year11, ?int $year12, ?int $userId = null): array
    {
        if ($year11) Timetable::where('admission_year', $year11)->delete();
        if ($year12) Timetable::where('admission_year', $year12)->delete();

        $generated = 0;
        $errors = [];

        DB::transaction(function () use ($year11, $year12, $userId, &$generated, &$errors) {
            $rooms = Room::where('is_active', true)->orderBy('name')->get();
            if ($rooms->isEmpty()) {
                $errors[] = "No active rooms available";
                return;
            }

            $days = Timetable::getDaysOfWeek();
            $periods = Timetable::getPeriodTimes();

            $batches = [];
            $compatibilityMatrices = [];

            foreach (['11' => $year11, '12' => $year12] as $class => $year) {
                if (!$year) continue;

                $subjects = $this->getSubjectsForLegacyBatch($year);
                $requirements = [];
                foreach ($subjects as $subject) {
                    $total = ($class == '11') ? $subject->total_11 : $subject->total_12;
                    $count = ($total > 50) ? 6 : 3;

                    $requirements[] = [
                        'subject' => $subject,
                        'count' => $count,
                        'scheduled' => 0
                    ];
                }
                $batches[$year] = $requirements;
                $compatibilityMatrices[$year] = $this->getLegacySubjectCompatibilityMatrix($year, $subjects);
            }

            $roomUsage = [];
            $scheduleMap = [];

            foreach ($days as $day) {
                foreach ($periods as $periodNum => $times) {
                    $roomUsage[$day][$periodNum] = [];
                }
            }

            foreach ($days as $day) {
                foreach ($periods as $periodNum => $times) {
                    foreach ($batches as $year => &$requirements) {
                        $scheduledInThisSlot = [];
                        $madeProgress = true;
                        while ($madeProgress) {
                            $madeProgress = false;
                            $bestReqIndex = -1;
                            $maxRemaining = -1;

                            foreach ($requirements as $index => $req) {
                                if ($req['scheduled'] >= $req['count']) continue;
                                if (in_array($req['subject']->id, $scheduledInThisSlot)) continue;

                                if (isset($scheduleMap[$year][$day]) && in_array($req['subject']->id, $scheduleMap[$year][$day])) {
                                    continue;
                                }

                                $compatible = true;
                                foreach ($scheduledInThisSlot as $scheduledId) {
                                    if (!$compatibilityMatrices[$year][$req['subject']->id][$scheduledId]) {
                                        $compatible = false;
                                        break;
                                    }
                                }
                                if (!$compatible) continue;

                                $remaining = $req['count'] - $req['scheduled'];
                                $isCompulsory = $req['subject']->type === 'Compulsory' ? 1 : 0;
                                $score = ($isCompulsory * 1000) + ($remaining * 10);

                                if ($score > $maxRemaining) {
                                    $maxRemaining = $score;
                                    $bestReqIndex = $index;
                                }
                            }

                            if ($bestReqIndex != -1) {
                                $candidate = &$requirements[$bestReqIndex];

                                $assignedRoom = null;
                                foreach ($rooms as $room) {
                                    if (!in_array($room->id, $roomUsage[$day][$periodNum])) {
                                        $assignedRoom = $room;
                                        break;
                                    }
                                }

                                if ($assignedRoom) {
                                    Timetable::create([
                                        'admission_year' => $year,
                                        'day_of_week' => $day,
                                        'period_number' => $periodNum,
                                        'subject_id' => $candidate['subject']->id,
                                        'room_id' => $assignedRoom->id,
                                        'staff_id' => null,
                                        'start_time' => $times['start'],
                                        'end_time' => $times['end'],
                                        'created_by' => $userId,
                                        'updated_by' => $userId,
                                    ]);

                                    $candidate['scheduled']++;
                                    $roomUsage[$day][$periodNum][] = $assignedRoom->id;
                                    $scheduleMap[$year][$day][] = $candidate['subject']->id;
                                    $scheduledInThisSlot[] = $candidate['subject']->id;
                                    $generated++;
                                    $madeProgress = true;
                                }
                            }
                        }
                    }
                }
            }
        });

        return ['generated' => $generated, 'errors' => $errors];
    }

    private function getLegacySubjectCompatibilityMatrix($year, $subjects): array
    {
        $matrix = [];
        $subjectIds = $subjects->pluck('id')->toArray();

        $subjectStudents = [];
        foreach ($subjectIds as $id) {
            $subjectStudents[$id] = DB::table('student_compulsory_subjects')
                ->where('subject_id', $id)
                ->join('admissions', 'student_compulsory_subjects.student_id', '=', 'admissions.student_id')
                ->where('admissions.admission_year', $year)
                ->pluck('student_compulsory_subjects.student_id')
                ->merge(
                    DB::table('student_elective_subjects')
                    ->where('subject_id', $id)
                    ->join('admissions', 'student_elective_subjects.student_id', '=', 'admissions.student_id')
                    ->where('admissions.admission_year', $year)
                    ->pluck('student_elective_subjects.student_id')
                )
                ->unique()
                ->toArray();
        }

        foreach ($subjectIds as $id1) {
            foreach ($subjectIds as $id2) {
                if ($id1 == $id2) {
                    $matrix[$id1][$id2] = false;
                    continue;
                }
                $intersection = array_intersect($subjectStudents[$id1], $subjectStudents[$id2]);
                $matrix[$id1][$id2] = empty($intersection);
            }
        }

        return $matrix;
    }

    private function getSubjectsForLegacyBatch($year): Collection
    {
        $subjectIds = Student::join('admissions', 'students.id', '=', 'admissions.student_id')
            ->where('admissions.admission_year', $year)
            ->whereIn('admissions.status', ['Admitted', 'Active'])
            ->join('student_compulsory_subjects', 'students.id', '=', 'student_compulsory_subjects.student_id')
            ->pluck('student_compulsory_subjects.subject_id')
            ->merge(
                Student::join('admissions', 'students.id', '=', 'admissions.student_id')
                    ->where('admissions.admission_year', $year)
                    ->whereIn('admissions.status', ['Admitted', 'Active'])
                    ->join('student_elective_subjects', 'students.id', '=', 'student_elective_subjects.student_id')
                    ->pluck('student_elective_subjects.subject_id')
            )
            ->unique();

        return Subject::whereIn('id', $subjectIds)->orderBy('name')->get();
    }
}
