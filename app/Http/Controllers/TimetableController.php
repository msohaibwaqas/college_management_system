<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Services\TimetableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    public function __construct(
        protected TimetableService $timetableService
    ) {}

    public function index(Request $request)
    {
        $selectedDay = $request->input('day_of_week', 'Monday');
        $data = $this->timetableService->getLegacyIndexData($request->admission_year, $selectedDay);

        return view('timetables.index', $data);
    }

    public function create()
    {
        $data = $this->timetableService->getLegacyCreateData();
        return view('timetables.create', $data);
    }

    public function store(Request $request)
    {
        $rules = [
            'admission_year' => 'required|integer',
            'subject_group_id' => 'required|exists:subject_groups,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday',
            'period_number' => 'required|integer|min:1|max:7',
            'room_id' => 'nullable|exists:rooms,id',
            'staff_id' => 'nullable|exists:staff,id',
            'is_break' => 'sometimes|boolean',
        ];

        if (!$request->boolean('is_break')) {
            $rules['subject_id'] = 'required|exists:subjects,id';
        }

        $request->validate($rules);

        $isBreak = $request->boolean('is_break');

        if (!$isBreak) {
            $conflicts = $this->timetableService->checkLegacyConflicts(
                $request->admission_year,
                $request->subject_group_id,
                $request->day_of_week,
                $request->period_number,
                $request->subject_id,
                $request->room_id,
                $request->staff_id
            );

            if (!empty($conflicts)) {
                return back()->withErrors(['conflict' => implode(' ', $conflicts)])->withInput();
            }
        }

        $this->timetableService->storeLegacyEntry($request->all(), Auth::id());

        return redirect()->route('admin.timetables.index', [
            'admission_year' => $request->admission_year,
            'day_of_week' => $request->day_of_week,
        ])->with('success', 'Timetable entry created successfully!');
    }

    public function generateForm()
    {
        $data = $this->timetableService->getLegacyGenerateFormData();
        return view('timetables.generate', $data);
    }

    public function generateTimetable(Request $request)
    {
        $request->validate([
            'admission_year_11' => 'nullable|integer',
            'admission_year_12' => 'nullable|integer',
        ]);

        $year11 = $request->admission_year_11;
        $year12 = $request->admission_year_12;

        if (!$year11 && !$year12) {
            return back()->withErrors(['error' => 'Please select at least one admission year.']);
        }

        $result = $this->timetableService->generateAutomaticLegacyTimetables($year11, $year12, Auth::id());

        if (!empty($result['errors'])) {
            return redirect()->route('timetables.generate.form')
                ->withErrors(['error' => implode(', ', $result['errors'])]);
        }

        return redirect()->route('admin.timetables.index')
            ->with('success', "Timetable generated successfully! {$result['generated']} periods created.");
    }

    public function destroy(Timetable $timetable)
    {
        $year = $timetable->admission_year;
        $day = $timetable->day_of_week;
        $timetable->delete();

        return redirect()->route('admin.timetables.index', [
            'admission_year' => $year,
            'day_of_week' => $day,
        ])->with('success', 'Timetable entry deleted successfully!');
    }

    public function destroyAll(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
        ]);

        $deleted = Timetable::where('admission_year', $request->admission_year)->delete();

        return redirect()->route('timetables.index', ['admission_year' => $request->admission_year])
            ->with('success', "Deleted {$deleted} timetable entries.");
    }
}
