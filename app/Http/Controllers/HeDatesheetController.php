<?php

namespace App\Http\Controllers;

use App\Models\HeAdmission;
use App\Models\HeDatesheet;
use App\Models\Program;
use App\Models\Semester;
use App\Services\DatesheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HeDatesheetController extends Controller
{
    protected DatesheetService $datesheetService;

    public function __construct(DatesheetService $datesheetService)
    {
        $this->datesheetService = $datesheetService;
    }

    /**
     * Display a listing of datesheets
     */
    public function index(Request $request)
    {
        $query = HeDatesheet::with(['program', 'bsSubject'])
            ->orderBy('exam_date', 'desc');

        if ($request->filled('program_id')) {
            $query->forProgram($request->program_id);
        }

        if ($request->filled('semester_no')) {
            $query->forSemester($request->semester_no);
        }

        if ($request->filled('exam_type')) {
            $query->forExamType($request->exam_type);
        }

        if ($request->filled('academic_year')) {
            $query->forAcademicYear($request->academic_year);
        }

        $datesheets = $query->paginate(25);

        $programs = Program::orderBy('name')->get();
        $academicYears = HeAdmission::distinctAdmissionYears();

        return view('he.datesheets.index', compact('datesheets', 'programs', 'academicYears'));
    }

    /**
     * Show the form for creating a new datesheet
     */
    public function create()
    {
        $programs = Program::orderBy('name')->get();
        $academicYears = HeAdmission::distinctAdmissionYears();

        return view('he.datesheets.create', compact('programs', 'academicYears'));
    }

    /**
     * Store a newly created datesheet
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'exam_type' => 'required|in:Mid,Final',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'academic_year' => 'required|integer',
        ]);

        $result = $this->datesheetService->storeHeDatesheet($data, Auth::id());

        if (!$result['success']) {
            return back()->withInput()->with('error', $result['error']);
        }

        return redirect()->route('he.datesheets.index')
            ->with('success', 'Datesheet entry created successfully.');
    }

    /**
     * Show the form for editing the specified datesheet
     */
    public function edit(HeDatesheet $heeDatesheet)
    {
        $programs = Program::orderBy('name')->get();
        $subjects = Semester::where('program_id', $heeDatesheet->program_id)
            ->where('semester_no', $heeDatesheet->semester_no)
            ->with('bsSubject')
            ->get();

        return view('he.datesheets.edit', compact('heeDatesheet', 'programs', 'subjects'));
    }

    /**
     * Update the specified datesheet
     */
    public function update(Request $request, HeDatesheet $heeDatesheet)
    {
        $request->validate([
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        $heeDatesheet->update([
            'exam_date' => $request->exam_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('he.datesheets.index')
            ->with('success', 'Datesheet updated successfully.');
    }

    /**
     * Remove the specified datesheet
     */
    public function destroy(HeDatesheet $heeDatesheet)
    {
        $heeDatesheet->deleted_by = Auth::id();
        $heeDatesheet->save();
        $heeDatesheet->delete();

        return redirect()->route('he.datesheets.index')
            ->with('success', 'Datesheet deleted successfully.');
    }

    /**
     * Show datesheet summary/printable view
     */
    public function summary(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer',
            'exam_type' => 'required|in:Mid,Final',
            'academic_year' => 'required|integer',
        ]);

        $datesheets = $this->datesheetService->getHeSummary(
            (int) $request->program_id,
            (int) $request->semester_no,
            $request->exam_type,
            (int) $request->academic_year
        );

        $program = Program::find($request->program_id);

        return view('he.datesheets.summary', compact('datesheets', 'program', 'request'));
    }

    /**
     * Generate datesheet automatically
     */
    public function generate()
    {
        $programs = Program::orderBy('name')->get();

        return view('he.datesheets.generate', compact('programs'));
    }

    /**
     * Store automatically generated datesheet
     */
    public function storeGenerated(Request $request)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'exam_type' => 'required|in:Mid,Final',
            'academic_year' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'default_start_time' => 'required',
            'default_end_time' => 'required|after:default_start_time',
        ]);

        $result = $this->datesheetService->generateHeAutomaticDatesheet($data, Auth::id());

        if (!$result['success']) {
            return back()->with('error', $result['error']);
        }

        $createdCount = $result['created_count'];
        $bestGap = $result['best_gap'];

        if ($createdCount == 0) {
            return redirect()->route('he.datesheets.index')
                ->with('error', 'No new datesheet entries created. All subjects already have scheduled exams.');
        }

        return redirect()->route('he.datesheets.index')
            ->with('success', "Datesheet generated for $createdCount subjects with $bestGap days gap (Sundays excluded).");
    }

    /**
     * Get subjects for a program and semester (AJAX)
     */
    public function getSubjects(Request $request)
    {
        $programId = $request->program_id;
        $semesterNo = $request->semester_no;

        if (!$programId || !$semesterNo) {
            return response()->json([]);
        }

        $subjects = $this->datesheetService->getHeSemesterSubjects((int) $programId, (int) $semesterNo);

        return response()->json($subjects);
    }
}
