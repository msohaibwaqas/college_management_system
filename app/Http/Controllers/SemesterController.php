<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SemesterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $semesters = \App\Models\Semester::with(['program', 'bsSubject'])
            ->whereHas('program')
            ->get();
        return view('he.semesters.index', compact('semesters'));
    }

    /**
     * Get available subjects for a program (exclude already assigned).
     */
    public function availableSubjects(Request $request)
    {
        \Log::info('Fetching available subjects for program: ' . $request->program_id);
        
        $request->validate([
            'program_id' => 'required|exists:programs,id'
        ]);

        $assignedSubjectIds = \App\Models\Semester::where('program_id', $request->program_id)
            ->pluck('bs_subject_id')
            ->unique()
            ->filter()
            ->toArray();

        \Log::info('Assigned subject IDs: ' . json_encode($assignedSubjectIds));

        $availableSubjects = \App\Models\BsSubject::whereNotIn('id', $assignedSubjectIds)->get();

        return response()->json($availableSubjects);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $programs = \App\Models\Program::all();
        $subjects = []; // Force AJAX loading
        return view('he.semesters.create', compact('programs', 'subjects'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'bs_subject_id' => 'required|exists:bs_subjects,id',
            'semester_no' => 'required|integer|min:1|max:10',
        ]);

        $validated['created_by'] = auth()->id();

        try {
            \App\Models\Semester::create($validated);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'This subject is already assigned to this program and semester.']);
        }

        return redirect()->route('semesters.index')->with('success', 'Subject added to curriculum.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Semester $semester)
    {
        $semester->delete();
        return redirect()->route('semesters.index')->with('success', 'Subject removed from curriculum.');
    }
}
