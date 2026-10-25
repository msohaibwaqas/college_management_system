<?php

namespace App\Http\Controllers;

use App\Models\BsSubject;
use App\Models\HeAdmission;
use App\Models\HeTimetable;
use App\Models\Program;
use App\Models\Room;
use App\Models\Semester;
use App\Models\TimeSlot;
use App\Services\TimetableService;
use Illuminate\Http\Request;

class HeTimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService
    ) {}

    public function index(Request $request)
    {
        $programs = Program::orderBy('name')->get();
        $admissionYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');

        $timetables = collect();
        $selectedProgram = null;
        $selectedSemester = null;
        $selectedYear = null;

        if ($request->filled('program_id') && $request->filled('semester_no') && $request->filled('admission_year')) {
            $selectedProgram = $request->program_id;
            $selectedSemester = $request->semester_no;
            $selectedYear = $request->admission_year;

            $timetables = HeTimetable::with(['subject', 'room', 'staff'])
                ->where('program_id', $selectedProgram)
                ->where('semester_no', $selectedSemester)
                ->where('admission_year', $selectedYear)
                ->orderBy('day_of_week')
                ->orderBy('period_number')
                ->get()
                ->groupBy('day_of_week');
        }

        $timeSlots = TimeSlot::orderBy('slot_number')->get();

        return view('he.timetables.index', compact(
            'programs', 'admissionYears', 'timetables', 'timeSlots',
            'selectedProgram', 'selectedSemester', 'selectedYear'
        ));
    }

    public function create(Request $request)
    {
        $programs = Program::orderBy('name')->get();
        $admissionYears = HeAdmission::select('admission_year')
            ->distinct()
            ->orderByDesc('admission_year')
            ->pluck('admission_year');
        $rooms = Room::where('is_active', true)->forLectures()->orderBy('name')->get();
        $days = HeTimetable::getDaysOfWeek();
        $timeSlots = TimeSlot::orderBy('slot_number')->get();

        $subjects = collect();
        $preselectedProgram = $request->query('program_id');
        $preselectedSemester = $request->query('semester_no');
        $preselectedYear = $request->query('admission_year');

        if ($preselectedProgram && $preselectedSemester) {
            $semesterSubjectIds = Semester::where('program_id', $preselectedProgram)
                ->where('semester_no', $preselectedSemester)
                ->pluck('bs_subject_id');
            $subjects = BsSubject::whereIn('id', $semesterSubjectIds)->orderBy('name')->get();
        }

        return view('he.timetables.create', compact(
            'programs', 'admissionYears', 'subjects', 'rooms', 'days', 'timeSlots',
            'preselectedProgram', 'preselectedSemester', 'preselectedYear'
        ));
    }

    public function getSemesterSubjects(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1|max:8',
        ]);

        $semesterSubjectIds = Semester::where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->pluck('bs_subject_id');

        $subjects = BsSubject::whereIn('id', $semesterSubjectIds)->orderBy('name')->get();

        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1|max:8',
            'admission_year' => 'required|integer|min:2020|max:2050',
            'day_of_week' => 'required|string',
            'period_number' => 'required|integer|exists:time_slots,slot_number',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'room_id' => 'required|exists:rooms,id',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $result = $this->timetableService->storeHeTimetable($data);

        if (!$result['success'] && !empty($result['conflicts'])) {
            return back()->withErrors(['conflict' => implode(' | ', $result['conflicts'])])->withInput();
        }

        $message = "Created {$result['created']} timetable entries.";
        if (!empty($result['conflicts'])) {
            $message .= " Skipped due to conflicts: " . implode(' | ', $result['conflicts']);
            return redirect()->route('he.timetables.index', [
                'program_id' => $request->program_id,
                'semester_no' => $request->semester_no,
                'admission_year' => $request->admission_year,
            ])->with('warning', $message);
        }

        return redirect()->route('he.timetables.index', [
            'program_id' => $request->program_id,
            'semester_no' => $request->semester_no,
            'admission_year' => $request->admission_year,
        ])->with('success', $message);
    }

    public function clone(Request $request)
    {
        $request->validate([
            'source_program_id' => 'required|exists:programs,id',
            'source_semester_no' => 'required|integer|min:1|max:8',
            'source_admission_year' => 'required|integer',
            'target_program_id' => 'required|exists:programs,id',
            'target_semester_no' => 'required|integer|min:1|max:8',
            'target_admission_year' => 'required|integer',
        ]);

        $result = $this->timetableService->cloneHeTimetable(
            (int)$request->source_program_id,
            (int)$request->source_semester_no,
            (int)$request->source_admission_year,
            (int)$request->target_program_id,
            (int)$request->target_semester_no,
            (int)$request->target_admission_year
        );

        if (!$result['success']) {
            return back()->with('error', $result['error'] ?? 'Could not clone timetable.');
        }

        $message = "Successfully cloned {$result['cloned']} entries.";
        if (!empty($result['conflicts'])) {
            $message .= " Skipped " . count($result['conflicts']) . " entries due to conflicts: " . implode(' | ', $result['conflicts']);
            return redirect()->route('he.timetables.index', [
                'program_id' => $request->target_program_id,
                'semester_no' => $request->target_semester_no,
                'admission_year' => $request->target_admission_year,
            ])->with('warning', $message);
        }

        return redirect()->route('he.timetables.index', [
            'program_id' => $request->target_program_id,
            'semester_no' => $request->target_semester_no,
            'admission_year' => $request->target_admission_year,
        ])->with('success', $message);
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer',
            'admission_year' => 'required|integer',
        ]);

        $deleted = HeTimetable::where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->where('admission_year', $request->admission_year)
            ->delete();

        return redirect()->route('he.timetables.index', [
            'program_id' => $request->program_id,
            'semester_no' => $request->semester_no,
            'admission_year' => $request->admission_year,
        ])->with('success', "Deleted {$deleted} timetable entries.");
    }

    public function destroy(HeTimetable $heTimetable)
    {
        $programId = $heTimetable->program_id;
        $semester = $heTimetable->semester_no;
        $year = $heTimetable->admission_year;
        $heTimetable->delete();

        return redirect()->route('he.timetables.index', [
            'program_id' => $programId,
            'semester_no' => $semester,
            'admission_year' => $year,
        ])->with('success', 'HE Timetable entry deleted successfully!');
    }
}
