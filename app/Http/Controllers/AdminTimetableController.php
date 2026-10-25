<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\HeAdmission;
use App\Models\Room;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\TimetableEntry;
use App\Services\CustomCollegeTimetableBuilder;
use App\Services\TimetableService;
use App\Services\TimetableValidatorService;
use Illuminate\Http\Request;

class AdminTimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService
    ) {}

    public function index(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $staffMembers = Staff::where('category', 'teaching')->where('status', 'active')->get()->sortBy('full_name');
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        $availableYears = Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterDay = $request->query('day', 'Monday');
        $filterYear = $request->query('admission_year', date('Y'));
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $entries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->where('day_of_week', $filterDay)
            ->when($filterYear, function ($q) use ($filterYear) {
                return $q->where('admission_year', $filterYear);
            })
            ->get();

        $allYearEntries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->when($filterYear, function ($q) use ($filterYear) {
                return $q->where('admission_year', $filterYear);
            })
            ->get();

        $matrixGrid = $this->timetableService->buildMatrixGrid($allYearEntries);
        $availableSections = $this->timetableService->getAvailableSectionsForYear($filterYear);

        return view('admin.timetable.index', compact(
            'timeSlots', 'subjects', 'staffMembers', 'entries', 'filterDay',
            'daysOfWeek', 'allYears', 'filterYear', 'rooms', 'matrixGrid', 'availableSections'
        ));
    }

    public function store(Request $request, TimetableValidatorService $validator)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'section' => 'required|string|max:100',
            'admission_year' => 'required|string|max:20',
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $result = $this->timetableService->storeEntryBlock($data, $validator);

        if (!$result['success']) {
            return back()->with('error', $result['error'])->withInput();
        }

        return back()->with('success', $result['days_count'] > 1
            ? 'Weekly timetable scheduled successfully.'
            : 'Timetable block scheduled successfully.');
    }

    public function update(Request $request, TimetableEntry $entry, TimetableValidatorService $validator)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'required|exists:staff,id',
            'section' => 'required|string|max:100',
            'admission_year' => 'required|string|max:20',
            'day_of_week' => 'required|string',
            'time_slot_id' => 'required|exists:time_slots,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $result = $this->timetableService->updateEntryBlock($entry, $data, $validator);

        if (!$result['success']) {
            return back()->with('error', $result['error'])->withInput();
        }

        return back()->with('success', $result['days_count'] > 1
            ? 'Weekly timetable updated successfully.'
            : 'Timetable block updated successfully.');
    }

    public function destroy(TimetableEntry $entry)
    {
        $entry->delete();
        return back()->with('success', 'Timetable block removed successfully.');
    }

    public function bulkDestroy(TimetableEntry $entry)
    {
        $deleted = $this->timetableService->bulkDestroyEntry($entry);
        return back()->with('success', "Removed $deleted timetable blocks across the week.");
    }

    public function summary(Request $request)
    {
        $year = $request->query('admission_year');
        if (!$year) {
            return back()->with('error', 'Please select an admission year first.');
        }

        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $entries = TimetableEntry::with(['subject', 'staff', 'timeSlot', 'room'])
            ->where('admission_year', $year)
            ->get();

        $grid = $this->timetableService->buildMatrixGrid($entries);

        return view('admin.timetable.summary', compact('timeSlots', 'daysOfWeek', 'grid', 'year'));
    }

    public function cloneYear(Request $request)
    {
        $request->validate([
            'source_year' => 'required|string',
            'target_year' => 'required|string|different:source_year',
        ]);

        $result = $this->timetableService->cloneTimetableYear($request->source_year, $request->target_year);

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', "Successfully cloned {$result['count']} entries from {$request->source_year} to {$request->target_year}.");
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
            'room_id' => 'nullable|exists:rooms,id',
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
            'matrix' => $matrix,
        ]);
    }

    public function teacherBatchIndex(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $staffMembers = Staff::where('category', 'teaching')->where('status', 'active')->get()->sortBy('full_name');
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        $availableYears = Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterYear = $request->query('admission_year', date('Y'));
        $filterSubjectId = $request->query('subject_id');
        $filterStaffId = $request->query('staff_id');
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $availableSections = $this->timetableService->getAvailableSectionsForYear($filterYear, $filterSubjectId);

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

        $result = $this->timetableService->storeTeacherBatch(
            $request->input('admission_year'),
            (int)$request->input('subject_id'),
            (int)$request->input('staff_id'),
            $request->input('assignments'),
            $validator
        );

        if (!$result['success']) {
            return back()->with('error', $result['error'])->withInput();
        }

        return redirect()->route('admin.timetables.teacher_batch', [
            'admission_year' => $request->input('admission_year'),
            'subject_id' => $request->input('subject_id'),
            'staff_id' => $request->input('staff_id'),
        ])->with('success', "Successfully scheduled {$result['created_count']} lecture block(s) across {$result['active_slots_count']} lecture slot(s) for teacher in 1 go!");
    }

    public function subjectBatchIndex(Request $request)
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        $subjects = Subject::orderBy('name')->get();
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();

        $availableYears = Admissions::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $availableHeYears = HeAdmission::distinct()->orderBy('admission_year', 'desc')->pluck('admission_year');
        $allYears = $availableYears->merge($availableHeYears)->unique()->sortDesc();

        $filterYear = $request->query('admission_year', date('Y'));
        $filterSubjectId = $request->query('subject_id');
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $availableSections = $this->timetableService->getAvailableSectionsForYear($filterYear, $filterSubjectId);

        $subjectTeachers = collect();
        $existingSubjectAllocations = [];
        $teacherSectionsMap = [];

        if ($filterSubjectId && $filterYear) {
            $batchData = $this->timetableService->getSubjectBatchData((int)$filterSubjectId, $filterYear);
            $subjectTeachers = $batchData['subject_teachers'];
            $existingSubjectAllocations = $batchData['existing_allocations'];
            $teacherSectionsMap = $batchData['teacher_sections_map'];
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

        $result = $this->timetableService->storeSubjectBatch(
            $request->input('admission_year'),
            (int)$request->input('subject_id'),
            $request->input('assignments', []),
            $validator
        );

        if (!$result['success']) {
            return back()->with('error', $result['error'])->withInput();
        }

        return redirect()->route('admin.timetables.subject_batch', [
            'admission_year' => $request->input('admission_year'),
            'subject_id' => $request->input('subject_id'),
        ])->with('success', "Successfully scheduled {$result['created_count']} parallel lecture block(s) across {$result['parsed_rows_count']} parallel class assignment(s) for the Subject Department in 1 go!");
    }
}
