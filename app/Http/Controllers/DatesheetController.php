<?php

namespace App\Http\Controllers;

use App\Models\Admissions;
use App\Models\Datesheet;
use App\Models\Subject;
use App\Services\DatesheetService;
use Illuminate\Http\Request;

class DatesheetController extends Controller
{
    protected DatesheetService $datesheetService;

    public function __construct(DatesheetService $datesheetService)
    {
        $this->datesheetService = $datesheetService;
    }

    public function index()
    {
        $datesheets = Datesheet::with('subject')
            ->orderBy('test_date')
            ->orderBy('admission_year')
            ->get();

        return view('datesheets.index', compact('datesheets'));
    }

    public function create()
    {
        $admissionYears = Admissions::distinctAdmissionYears();
        $subjects = Subject::orderBy('name')->get();

        return view('datesheets.create', compact('admissionYears', 'subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'admission_year' => 'required|integer',
            'test_date' => 'required|date|after_or_equal:today',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $result = $this->datesheetService->storeManualDatesheet(
            (int) $request->admission_year,
            $request->test_date,
            $request->subject_ids
        );

        if (!$result['success']) {
            return back()->with('error', $result['error'])->withInput();
        }

        $savedCount = $result['saved_count'];
        $conflicts = $result['conflicts'];

        if (count($conflicts) > 0) {
            return redirect()->route('datesheets.index')
                ->with('warning', "Saved $savedCount tests. Conflicts: " . implode(' | ', $conflicts));
        }

        return redirect()->route('datesheets.index')
            ->with('success', "Successfully scheduled $savedCount tests.");
    }

    public function destroy(Datesheet $datesheet)
    {
        $datesheet->delete();
        return back()->with('success', 'Test deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:datesheets,id',
        ]);

        Datesheet::whereIn('id', $request->ids)->delete();

        return back()->with('success', 'Selected tests deleted successfully.');
    }

    public function summary(Request $request)
    {
        $schedule = $this->datesheetService->getSummarySchedule(
            $request->start_date,
            $request->end_date
        );

        return view('datesheets.summary', compact('schedule'));
    }

    public function generate()
    {
        $admissionYears = Admissions::distinctAdmissionYears();

        return view('datesheets.generate', compact('admissionYears'));
    }

    public function storeGenerated(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'admission_years' => 'required|array',
            'admission_years.*' => 'integer',
        ]);

        $result = $this->datesheetService->generateAutomaticDatesheet(
            $request->start_date,
            $request->end_date,
            $request->admission_years
        );

        if (!$result['success']) {
            return back()->with('error', $result['message'] ?? 'Unable to generate datesheet.')->withInput();
        }

        $totalScheduled = $result['total_scheduled'];
        $errors = $result['errors'];

        if (count($errors) > 0) {
            return redirect()->route('datesheets.index')
                ->with('warning', "Scheduled $totalScheduled tests. Issues: " . implode(' | ', $errors));
        }

        return redirect()->route('datesheets.index')
            ->with('success', "Successfully generated datesheet with $totalScheduled tests.");
    }
}
