<?php

namespace App\Http\Controllers;

use App\Models\HeTimetable;
use App\Models\HeAdmission;
use App\Models\Program;
use App\Models\BsSubject;
use App\Models\Semester;
use App\Models\Room;
use App\Models\Staff;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeTimetableController extends Controller
{
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

        // If program and semester are pre-selected, filter subjects to that semester's curriculum
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

    /**
     * AJAX: Get subjects for a specific program + semester
     */
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
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1|max:8',
            'admission_year' => 'required|integer|min:2020|max:2050',
            'day_of_week' => 'required|string',
            'period_number' => 'required|integer|exists:time_slots,slot_number',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'room_id' => 'required|exists:rooms,id',
            'staff_id' => 'nullable|exists:staff,id',
        ]);

        $timeSlot = TimeSlot::where('slot_number', $request->period_number)->firstOrFail();

        // Resolve which days to schedule
        $daysToSchedule = match($request->day_of_week) {
            'All Week' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
            'First Three Days' => ['Monday', 'Tuesday', 'Wednesday'],
            'Last Three Days' => ['Thursday', 'Friday', 'Saturday'],
            default => [$request->day_of_week],
        };

        $allConflicts = [];
        $created = 0;

        foreach ($daysToSchedule as $day) {
            $conflicts = $this->checkConflicts(
                $request->program_id,
                $request->semester_no,
                $request->admission_year,
                $day,
                $request->period_number,
                $request->room_id,
                $request->staff_id
            );

            if (!empty($conflicts)) {
                $allConflicts[] = "{$day}: " . implode(', ', $conflicts);
                continue;
            }

            HeTimetable::create([
                'program_id' => $request->program_id,
                'semester_no' => $request->semester_no,
                'admission_year' => $request->admission_year,
                'day_of_week' => $day,
                'period_number' => $request->period_number,
                'bs_subject_id' => $request->bs_subject_id,
                'room_id' => $request->room_id,
                'staff_id' => $request->staff_id,
                'start_time' => $timeSlot->start_time,
                'end_time' => $timeSlot->end_time,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            $created++;
        }

        if (!empty($allConflicts) && $created === 0) {
            return back()->withErrors(['conflict' => implode(' | ', $allConflicts)])->withInput();
        }

        $message = "Created {$created} timetable entries.";
        if (!empty($allConflicts)) {
            $message .= " Skipped due to conflicts: " . implode(' | ', $allConflicts);
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

    private function checkConflicts($programId, $semester, $admissionYear, $day, $period, $roomId, $staffId)
    {
        $conflicts = [];

        // 1. Room conflict — Global across ALL HE batches and HSSC
        // A room can only be used once per day+period, regardless of batch
        $roomConflictHE = HeTimetable::where('day_of_week', $day)
            ->where('period_number', $period)
            ->where('room_id', $roomId)
            ->exists();
            
        $roomConflictHSSC = \App\Models\Timetable::where('day_of_week', $day)
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
                
            $staffConflictHSSC = \App\Models\Timetable::where('day_of_week', $day)
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
     * Clone a timetable from one batch/semester to another
     */
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

        // Check source has entries
        $sourceEntries = HeTimetable::where('program_id', $request->source_program_id)
            ->where('semester_no', $request->source_semester_no)
            ->where('admission_year', $request->source_admission_year)
            ->get();

        if ($sourceEntries->isEmpty()) {
            return back()->with('error', 'No timetable entries found in the source to clone.');
        }

        // Check target doesn't already have entries
        $targetExists = HeTimetable::where('program_id', $request->target_program_id)
            ->where('semester_no', $request->target_semester_no)
            ->where('admission_year', $request->target_admission_year)
            ->exists();

        if ($targetExists) {
            return back()->with('error', 'Target already has timetable entries. Please delete them first or choose a different target.');
        }

        $cloned = 0;
        $conflicts = [];

        DB::transaction(function () use ($sourceEntries, $request, &$cloned, &$conflicts) {
            foreach ($sourceEntries as $entry) {
                // Check for room/teacher conflicts in the target context
                $entryConflicts = $this->checkConflicts(
                    $request->target_program_id,
                    $request->target_semester_no,
                    $request->target_admission_year,
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
                $newEntry->program_id = $request->target_program_id;
                $newEntry->semester_no = $request->target_semester_no;
                $newEntry->admission_year = $request->target_admission_year;
                $newEntry->created_by = Auth::id();
                $newEntry->updated_by = Auth::id();
                $newEntry->save();
                $cloned++;
            }
        });

        $message = "Successfully cloned {$cloned} entries.";
        if (!empty($conflicts)) {
            $message .= " Skipped " . count($conflicts) . " entries due to conflicts: " . implode(' | ', $conflicts);
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

    /**
     * Delete all entries for a specific program/semester/batch
     */
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
