<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeSlot;
use App\Models\Subject;
use App\Models\Staff;
use App\Models\TimetableEntry;
use App\Services\TimetableValidatorService;

use App\Services\CustomCollegeTimetableBuilder;

class AdminTimetableController extends Controller
{
    public function index(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $staffMembers = Staff::where('category', 'teaching')->where('status', 'active')->get()->sortBy('full_name');
        $rooms = \App\Models\Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        // Fetch distinct admission years for the filter
        $availableYears = \App\Models\Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = \App\Models\HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterDay = $request->query('day', 'Monday');
        $filterYear = $request->query('admission_year', date('Y'));
        
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $entries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->where('day_of_week', $filterDay)
            ->when($filterYear, function($q) use ($filterYear) {
                return $q->where('admission_year', $filterYear);
            })
            ->get();

        // All entries for selected year to build weekly matrix grid
        $allYearEntries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->when($filterYear, function($q) use ($filterYear) {
                return $q->where('admission_year', $filterYear);
            })
            ->get();

        $matrixGrid = [];
        foreach ($allYearEntries as $entry) {
            $matrixGrid[$entry->time_slot_id][$entry->day_of_week][] = $entry;
        }

        // Unique section list for section filter / combined multi-select
        $availableSections = $this->getAvailableSectionsForYear($filterYear);

        return view('admin.timetable.index', compact('timeSlots', 'subjects', 'staffMembers', 'entries', 'filterDay', 'daysOfWeek', 'allYears', 'filterYear', 'rooms', 'matrixGrid', 'availableSections'));
    }

    public function store(Request $request, TimetableValidatorService $validator)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'section' => 'required|string|max:100',
            'admission_year' => 'required|string|max:20',
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $daysToSchedule = $request->day_of_week === 'All Week' 
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$request->day_of_week];

        $teacherClashes = [];
        $roomClashes = [];
        $sectionClashes = [];
        
        foreach ($daysToSchedule as $day) {
            // Teacher check
            if ($validator->hasTeacherClash($request->staff_id, $day, $request->time_slot_id, $request->subject_id, $request->admission_year)) {
                $teacherClashes[] = $day;
            }
            // Room check
            if ($request->room_id && $validator->hasRoomClash($request->room_id, $day, $request->time_slot_id, $request->staff_id, $request->admission_year)) {
                $roomClashes[] = $day;
            }
            // Section check
            if ($validator->hasSectionClash($request->section, $request->admission_year, $day, $request->time_slot_id)) {
                $sectionClashes[] = $day;
            }
        }

        if (count($teacherClashes) > 0 || count($roomClashes) > 0 || count($sectionClashes) > 0) {
            $msg = '';
            if (count($teacherClashes) > 0) $msg .= 'Teacher Clash on: ' . implode(', ', $teacherClashes) . '. ';
            if (count($roomClashes) > 0) $msg .= 'Room Clash on: ' . implode(', ', $roomClashes) . '. ';
            if (count($sectionClashes) > 0) {
                $clashDetail = $validator->getSectionClashDetails($request->section, $request->admission_year, $sectionClashes[0], $request->time_slot_id);
                $occupantInfo = $clashDetail ? ' (Currently assigned to ' . ($clashDetail->staff->full_name ?? 'another teacher') . ' for ' . ($clashDetail->subject->name ?? 'another subject') . ')' : '';
                $msg .= 'Section Clash: Section "' . $request->section . '" is already scheduled on ' . implode(', ', $sectionClashes) . $occupantInfo . '. Please delete or edit the existing entry first if you wish to reassign this section.';
            }
            return back()->with('error', trim($msg))->withInput();
        }

        foreach ($daysToSchedule as $day) {
            $data = $request->all();
            $data['day_of_week'] = $day;
            TimetableEntry::create($data);
        }

        return back()->with('success', count($daysToSchedule) > 1 ? 'Weekly timetable scheduled successfully.' : 'Timetable block scheduled successfully.');
    }

    public function update(Request $request, TimetableEntry $entry, TimetableValidatorService $validator)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'section' => 'required|string|max:100',
            'admission_year' => 'required|string|max:20',
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $daysToSchedule = $request->day_of_week === 'All Week' 
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
            : [$request->day_of_week];

        $teacherClashes = [];
        $roomClashes = [];
        $sectionClashes = [];

        foreach ($daysToSchedule as $day) {
            $ignoreId = ($day === $entry->day_of_week) ? $entry->id : null;

            if (!$ignoreId && $request->day_of_week === 'All Week') {
                $existingDayEntry = TimetableEntry::where('admission_year', $entry->admission_year)
                    ->where('section', $entry->section)
                    ->where('time_slot_id', $entry->time_slot_id)
                    ->where('day_of_week', $day)
                    ->first();
                if ($existingDayEntry) {
                    $ignoreId = $existingDayEntry->id;
                }
            }

            if ($validator->hasTeacherClash($request->staff_id, $day, $request->time_slot_id, $request->subject_id, $request->admission_year, $ignoreId)) {
                $teacherClashes[] = $day;
            }
            if ($request->room_id && $validator->hasRoomClash($request->room_id, $day, $request->time_slot_id, $request->staff_id, $request->admission_year, $ignoreId)) {
                $roomClashes[] = $day;
            }
            if ($validator->hasSectionClash($request->section, $request->admission_year, $day, $request->time_slot_id, $ignoreId)) {
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
                $clashDetail = $validator->getSectionClashDetails($request->section, $request->admission_year, $firstClashDay, $request->time_slot_id, $ignoreIdForDetail);
                $occupantInfo = $clashDetail ? ' (Currently assigned to ' . ($clashDetail->staff->full_name ?? 'another teacher') . ' for ' . ($clashDetail->subject->name ?? 'another subject') . ')' : '';
                $msg .= 'Section Clash: Section "' . $request->section . '" is already scheduled on ' . implode(', ', $sectionClashes) . $occupantInfo . '. ';
            }
            return back()->with('error', trim($msg))->withInput();
        }

        if ($request->day_of_week === 'All Week') {
            foreach ($daysToSchedule as $day) {
                TimetableEntry::updateOrCreate(
                    [
                        'admission_year' => $request->admission_year,
                        'section' => $request->section,
                        'day_of_week' => $day,
                        'time_slot_id' => $request->time_slot_id,
                    ],
                    [
                        'subject_id' => $request->subject_id,
                        'staff_id' => $request->staff_id,
                        'room_id' => $request->room_id,
                        'is_active' => true,
                    ]
                );
            }
        } else {
            $entry->update([
                'admission_year' => $request->admission_year,
                'day_of_week' => $request->day_of_week,
                'time_slot_id' => $request->time_slot_id,
                'subject_id' => $request->subject_id,
                'staff_id' => $request->staff_id,
                'room_id' => $request->room_id,
                'section' => $request->section,
            ]);
        }

        return back()->with('success', count($daysToSchedule) > 1 ? 'Weekly timetable updated successfully.' : 'Timetable block updated successfully.');
    }

    public function destroy(TimetableEntry $entry)
    {
        $entry->delete();
        return back()->with('success', 'Timetable block removed successfully.');
    }

    public function bulkDestroy(TimetableEntry $entry)
    {
        // Delete all matching entries for the whole week for this specific slot/teacher/subject/year
        $deleted = TimetableEntry::where('subject_id', $entry->subject_id)
            ->where('staff_id', $entry->staff_id)
            ->where('section', $entry->section)
            ->where('admission_year', $entry->admission_year)
            ->where('time_slot_id', $entry->time_slot_id)
            ->delete();

        return back()->with('success', "Removed $deleted timetable blocks across the week.");
    }

    public function summary(Request $request)
    {
        $year = $request->query('admission_year');
        if (!$year) return back()->with('error', 'Please select an admission year first.');

        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $entries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->where('admission_year', $year)
            ->get();

        $grid = [];
        foreach ($entries as $entry) {
            $grid[$entry->time_slot_id][$entry->day_of_week][] = $entry;
        }

        return view('admin.timetable.summary', compact('timeSlots', 'daysOfWeek', 'grid', 'year'));
    }
    public function cloneYear(Request $request)
    {
        $request->validate([
            'source_year' => 'required|string',
            'target_year' => 'required|string|different:source_year',
        ]);

        $sourceEntries = TimetableEntry::where('admission_year', $request->source_year)->get();
        
        if ($sourceEntries->isEmpty()) {
            return back()->with('error', "No entries found for admission year {$request->source_year} to clone.");
        }

        foreach ($sourceEntries as $entry) {
            $newEntry = $entry->replicate();
            $newEntry->admission_year = $request->target_year;
            $newEntry->is_active = true; // New clones are active by default
            $newEntry->save();
        }

        return back()->with('success', "Successfully cloned " . $sourceEntries->count() . " entries from {$request->source_year} to {$request->target_year}.");
    }

    public function toggleStatus(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $updated = TimetableEntry::where('admission_year', $request->admission_year)
            ->update(['is_active' => $request->is_active]);

        $status = $request->is_active ? 'activated' : 'archived';
        return back()->with('success', "Successfully $status $updated entries for admission year {$request->admission_year}.");
    }

    public function storeCombined(Request $request, CustomCollegeTimetableBuilder $builder, TimetableValidatorService $validator)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'section' => 'required',
            'admission_year' => 'required|string|max:20',
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'nullable|exists:rooms,id'
        ]);

        $sections = is_array($request->section) ? $request->section : array_map('trim', explode(',', $request->section));
        $data = $request->all();
        $data['section'] = $sections;

        $result = $builder->storeCombinedBlock($data, $validator);

        if (!$result['success']) {
            return back()->with('error', $result['message'])->withInput();
        }

        return back()->with('success', $result['message']);
    }

    public function checkSlotAvailability(Request $request, CustomCollegeTimetableBuilder $builder, TimetableValidatorService $validator)
    {
        $staffId = (int)$request->query('staff_id');
        $roomId = $request->query('room_id') ? (int)$request->query('room_id') : null;
        $admissionYear = $request->query('admission_year', date('Y'));
        $sectionsInput = $request->query('sections', []);

        $sections = is_array($sectionsInput) ? $sectionsInput : array_map('trim', explode(',', (string)$sectionsInput));

        $matrix = $builder->checkSlotAvailability($staffId, $roomId, $sections, $admissionYear, $validator);

        return response()->json([
            'success' => true,
            'matrix' => $matrix
        ]);
    }

    public function teacherBatchIndex(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $staffMembers = Staff::where('category', 'teaching')->where('status', 'active')->get()->sortBy('full_name');
        $rooms = \App\Models\Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        $availableYears = \App\Models\Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = \App\Models\HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterYear = $request->query('admission_year', date('Y'));
        $filterSubjectId = $request->query('subject_id');
        $filterStaffId = $request->query('staff_id');

        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $availableSections = $this->getAvailableSectionsForYear($filterYear, $filterSubjectId);

        $existingAssignments = [];
        if ($filterStaffId && $filterSubjectId && $filterYear) {
            $entries = TimetableEntry::where('staff_id', $filterStaffId)
                ->where('subject_id', $filterSubjectId)
                ->where('admission_year', $filterYear)
                ->where('is_active', true)
                ->get();

            foreach ($entries as $entry) {
                $existingAssignments[$entry->time_slot_id]['sections'][] = $entry->section;
                $existingAssignments[$entry->time_slot_id]['room_id'] = $entry->room_id;
                $existingAssignments[$entry->time_slot_id]['day_of_week'] = $entry->day_of_week;
            }

            // Deduplicate sections per slot
            foreach ($existingAssignments as $slotId => $data) {
                $existingAssignments[$slotId]['sections'] = array_unique($data['sections']);
            }
        }

        return view('admin.timetable.teacher_batch', compact(
            'timeSlots', 'subjects', 'staffMembers', 'rooms', 'allYears', 
            'filterYear', 'filterSubjectId', 'filterStaffId', 'daysOfWeek', 
            'availableSections', 'existingAssignments'
        ));
    }

    public function storeTeacherBatch(Request $request, TimetableValidatorService $validator)
    {
        $request->validate([
            'admission_year' => 'required|string|max:20',
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'assignments' => 'required|array',
        ]);

        $admissionYear = $request->input('admission_year');
        $subjectId = (int)$request->input('subject_id');
        $staffId = (int)$request->input('staff_id');
        $assignments = $request->input('assignments');

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

            // Run clash validation for each day of this slot
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
            return back()->with('error', 'Please check at least one lecture slot to schedule.')->withInput();
        }

        if (count($clashes) > 0) {
            return back()->with('error', implode(' ', array_unique($clashes)))->withInput();
        }

        // Save all assignments atomically
        $createdCount = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($activeSlots, $admissionYear, $subjectId, $staffId, &$createdCount) {
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

        return redirect()->route('admin.timetables.teacher_batch', [
            'admission_year' => $admissionYear,
            'subject_id' => $subjectId,
            'staff_id' => $staffId,
        ])->with('success', "Successfully scheduled {$createdCount} lecture block(s) across " . count($activeSlots) . " lecture slot(s) for teacher in 1 go!");
    }

    public function subjectBatchIndex(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $rooms = \App\Models\Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        $availableYears = \App\Models\Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = \App\Models\HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterYear = $request->query('admission_year', date('Y'));
        $filterSubjectId = $request->query('subject_id');

        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $availableSections = $this->getAvailableSectionsForYear($filterYear, $filterSubjectId);

        $subjectTeachers = collect();
        $existingSubjectAllocations = [];
        $teacherSectionsMap = [];

        if ($filterSubjectId && $filterYear) {
            // Get active teaching staff specializing in or assigned to this subject
            $subjectTeachers = Staff::where('category', 'teaching')
                ->where('status', 'active')
                ->where(function($q) use ($filterSubjectId) {
                    $q->whereHas('subjectSpecializations', function($sq) use ($filterSubjectId) {
                        $sq->where('subject_id', $filterSubjectId);
                    })->orWhereHas('studentTeacherSubjects', function($sq) use ($filterSubjectId) {
                        $sq->where('subject_id', $filterSubjectId);
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

            // Load existing entries for this subject & admission year
            $entries = TimetableEntry::with(['staff', 'room'])
                ->where('subject_id', $filterSubjectId)
                ->where('admission_year', $filterYear)
                ->where('is_active', true)
                ->get();

            // Group entries by time_slot_id and unique teacher/room/day grouping
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

            // Convert to indexed list per slot
            foreach ($grouped as $slotId => $groupList) {
                foreach ($groupList as $g) {
                    $g['sections'] = array_values(array_unique($g['sections']));
                    $existingSubjectAllocations[$slotId][] = $g;
                }
            }

            // Map teacher ID => array of section names assigned to that teacher for this subject and admission year
            $stsAssignments = \App\Models\StudentTeacherSubject::with(['student.subjectSections' => function($q) use ($filterSubjectId, $filterYear) {
                $q->where('subject_id', $filterSubjectId)->where('admission_year', $filterYear);
            }])
            ->where('subject_id', $filterSubjectId)
            ->whereHas('student.admissions', function($q) use ($filterYear) {
                $q->where('admission_year', $filterYear);
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
        }

        return view('admin.timetable.subject_batch', compact(
            'timeSlots', 'subjects', 'rooms', 'allYears', 'filterYear', 
            'filterSubjectId', 'daysOfWeek', 'availableSections', 
            'subjectTeachers', 'existingSubjectAllocations', 'teacherSectionsMap'
        ));
    }

    public function storeSubjectBatch(Request $request, TimetableValidatorService $validator)
    {
        $request->validate([
            'admission_year' => 'required|string|max:20',
            'subject_id' => 'required|exists:subjects,id',
            'assignments' => 'nullable|array',
        ]);

        $admissionYear = $request->input('admission_year');
        $subjectId = (int)$request->input('subject_id');
        $assignments = $request->input('assignments', []);

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

                // Check clashes for each scheduled day
                foreach ($daysToSchedule as $day) {
                    if ($validator->hasTeacherClash($staffId, $day, (int)$slotId, $subjectId, $admissionYear)) {
                        $teacherName = Staff::find($staffId)->full_name ?? "Teacher ID {$staffId}";
                        $clashes[] = "Teacher Clash: {$teacherName} is busy on {$day} during Lecture Slot {$slotId}.";
                    }

                    if ($roomId && $validator->hasRoomClash($roomId, $day, (int)$slotId, $staffId, $admissionYear)) {
                        $roomName = \App\Models\Room::find($roomId)->name ?? "Room ID {$roomId}";
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
            return back()->with('error', implode(' ', array_unique($clashes)))->withInput();
        }

        // Execute atomic save: clear old allocations for this subject/year and recreate all
        $createdCount = 0;
        \Illuminate\Support\Facades\DB::transaction(function () use ($parsedRows, $admissionYear, $subjectId, &$createdCount) {
            // Delete existing active entries for this subject and admission year
            TimetableEntry::where('subject_id', $subjectId)
                ->where('admission_year', $admissionYear)
                ->delete();

            // Insert new parsed rows
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

        return redirect()->route('admin.timetables.subject_batch', [
            'admission_year' => $admissionYear,
            'subject_id' => $subjectId,
        ])->with('success', "Successfully scheduled {$createdCount} parallel lecture block(s) across " . count($parsedRows) . " parallel class assignment(s) for the Subject Department in 1 go!");
    }

    private function getAvailableSectionsForYear(?string $admissionYear, ?int $subjectId = null)
    {
        if (!$admissionYear) {
            return collect(['M1', 'M2', 'E1', 'ICS-I', 'ICS-II', 'ICS-III', 'ICS-IV', 'FA-IT']);
        }

        // 1. Fetch sections from StudentSubjectSection for target admission year & subject
        $subjectSections = \App\Models\StudentSubjectSection::where('admission_year', $admissionYear)
            ->when($subjectId, function($q) use ($subjectId) {
                return $q->where('subject_id', $subjectId);
            })
            ->distinct()
            ->pluck('section');

        // 2. Fetch sections from TimetableEntry for target admission year & subject
        $timetableSections = TimetableEntry::where('admission_year', $admissionYear)
            ->when($subjectId, function($q) use ($subjectId) {
                return $q->where('subject_id', $subjectId);
            })
            ->distinct()
            ->pluck('section');

        $sections = $subjectSections->merge($timetableSections)
            ->filter()
            ->reject(function($s) {
                return str_contains($s, '+');
            })
            ->unique()
            ->sort()
            ->values();

        // 4. Fallback: If no subject-specific sections exist at all for this subject in this year, get all sections for this year
        if ($sections->isEmpty() && $subjectId) {
            $sections = \App\Models\StudentSubjectSection::where('admission_year', $admissionYear)
                ->distinct()
                ->pluck('section')
                ->unique()
                ->sort()
                ->values();
        }

        // 5. Ultimate fallback if no sections exist in DB for this admission year
        if ($sections->isEmpty()) {
            $sections = collect(['M1', 'M2', 'E1', 'ICS-I', 'ICS-II', 'ICS-III', 'ICS-IV', 'FA-IT']);
        }

        return $sections;
    }
}
