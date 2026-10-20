<?php

namespace App\Http\Controllers;

use App\Models\HeDatesheet;
use App\Models\Program;
use App\Models\BsSubject;
use App\Models\Semester;
use App\Models\HeAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HeDatesheetController extends Controller
{
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
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'exam_type' => 'required|in:Mid,Final',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'academic_year' => 'required|integer',
        ]);

        $userId = Auth::id();

        // Check for duplicate
        $exists = HeDatesheet::where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->where('bs_subject_id', $request->bs_subject_id)
            ->where('exam_type', $request->exam_type)
            ->where('academic_year', $request->academic_year)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A datesheet entry already exists for this subject/exam type.');
        }

        HeDatesheet::create([
            'program_id' => $request->program_id,
            'semester_no' => $request->semester_no,
            'bs_subject_id' => $request->bs_subject_id,
            'exam_type' => $request->exam_type,
            'exam_date' => $request->exam_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'academic_year' => $request->academic_year,
            'created_by' => $userId,
        ]);

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

        $datesheets = HeDatesheet::with(['program', 'bsSubject'])
            ->where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->where('exam_type', $request->exam_type)
            ->where('academic_year', $request->academic_year)
            ->orderBy('exam_date')
            ->get();

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
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'semester_no' => 'required|integer|min:1',
            'exam_type' => 'required|in:Mid,Final',
            'academic_year' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'default_start_time' => 'required',
            'default_end_time' => 'required|after:default_start_time',
        ]);

        $userId = Auth::id();

        // Get all subjects for this semester
        $subjects = Semester::where('program_id', $request->program_id)
            ->where('semester_no', $request->semester_no)
            ->with('bsSubject')
            ->get();

        if ($subjects->isEmpty()) {
            return back()->with('error', 'No subjects found for this semester.');
        }

        $subjectCount = $subjects->count();

        // Find best gap using simulation (max possible gap effectively)
        // Upper bound: Total days / (Subjects - 1)
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate);

        if ($subjectCount > 1) {
            $maxGapSearch = floor(($totalDays + 7) / ($subjectCount - 1)); // slightly loose upper bound
        } else {
            $maxGapSearch = 0;
        }

        $bestGap = -1;

        // Loop down to find largest gap that fits
        for ($gap = $maxGapSearch; $gap >= 0; $gap--) {
            // Simulate sim-schedule
            $tempDate = $startDate->copy();
            if ($tempDate->isSunday()) $tempDate->addDay(); // Move start to Monday if Sunday
            
            $fits = true;
            for ($i = 1; $i < $subjectCount; $i++) {
                $tempDate->addDays(1 + $gap);
                while ($tempDate->isSunday()) {
                    $tempDate->addDay();
                }
                
                if ($tempDate->gt($endDate)) {
                    $fits = false;
                    break;
                }
            }
            
            if ($fits) {
                $bestGap = $gap;
                break;
            }
        }

        if ($bestGap === -1) {
             return back()->with('error', "Date range is too short for $subjectCount subjects (excluding Sundays). Please extend the End Date.");
        }

        DB::beginTransaction();
        try {
            $currentDate = $startDate->copy();
            if ($currentDate->isSunday()) $currentDate->addDay();
            
            $createdCount = 0;

            foreach ($subjects as $semesterSubject) {
                // Check if exists (including soft deleted)
                $existing = HeDatesheet::withTrashed()
                    ->where('program_id', $request->program_id)
                    ->where('semester_no', $request->semester_no)
                    ->where('bs_subject_id', $semesterSubject->bs_subject_id)
                    ->where('exam_type', $request->exam_type)
                    ->where('academic_year', $request->academic_year)
                    ->first();

                if ($existing) {
                    // If it was deleted, restore and update it
                    if ($existing->trashed()) {
                        $existing->restore();
                        $existing->update([
                            'exam_date' => $currentDate->toDateString(),
                            'start_time' => $request->default_start_time,
                            'end_time' => $request->default_end_time,
                            'created_by' => $userId,
                        ]);
                        $createdCount++;
                    }
                    // If active, we skip it (preserve existing)
                } else {
                    HeDatesheet::create([
                        'program_id' => $request->program_id,
                        'semester_no' => $request->semester_no,
                        'bs_subject_id' => $semesterSubject->bs_subject_id,
                        'exam_type' => $request->exam_type,
                        'exam_date' => $currentDate->toDateString(),
                        'start_time' => $request->default_start_time,
                        'end_time' => $request->default_end_time,
                        'academic_year' => $request->academic_year,
                        'created_by' => $userId,
                    ]);
                    $createdCount++;
                }

                // Move to next date
                $currentDate->addDays(1 + $bestGap);
                while ($currentDate->isSunday()) {
                    $currentDate->addDay();
                }
            }

            DB::commit();

            if ($createdCount == 0) {
                 return redirect()->route('he.datesheets.index')
                    ->with('error', "No new datesheet entries created. All subjects already have scheduled exams.");
            }

            return redirect()->route('he.datesheets.index')
                ->with('success', "Datesheet generated for $createdCount subjects with $bestGap days gap (Sundays excluded).");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error generating datesheet: ' . $e->getMessage());
        }
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

        $subjects = Semester::where('program_id', $programId)
            ->where('semester_no', $semesterNo)
            ->with('bsSubject')
            ->get()
            ->map(function ($semester) {
                return [
                    'id' => $semester->bs_subject_id,
                    'name' => $semester->bsSubject->name ?? 'Unknown',
                    'code' => $semester->bsSubject->code ?? '',
                ];
            });

        return response()->json($subjects);
    }
}
