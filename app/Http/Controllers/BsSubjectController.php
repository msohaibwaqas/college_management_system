<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BsSubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subjects = \App\Models\BsSubject::all();
        return view('he.bs_subjects.index', compact('subjects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('he.bs_subjects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:15|unique:bs_subjects,code',
            'theory_credit_hours' => 'required|integer|min:0|max:6',
            'practical_credit_hours' => 'required|integer|min:0|max:6',
            'type' => 'required|in:Major,Minor,General,Elective',
        ]);

        $validated['created_by'] = auth()->id();

        \App\Models\BsSubject::create($validated);

        return redirect()->route('bs-subjects.index')->with('success', 'Subject created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\BsSubject $bsSubject)
    {
        return view('he.bs_subjects.edit', compact('bsSubject'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\BsSubject $bsSubject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:15|unique:bs_subjects,code,' . $bsSubject->id,
            'theory_credit_hours' => 'required|integer|min:0|max:6',
            'practical_credit_hours' => 'required|integer|min:0|max:6',
            'type' => 'required|in:Major,Minor,General,Elective',
        ]);

        $validated['updated_by'] = auth()->id();

        $bsSubject->update($validated);

        return redirect()->route('bs-subjects.index')->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\BsSubject $bsSubject)
    {
        $bsSubject->update(['deleted_by' => auth()->id()]);
        $bsSubject->delete();

        return redirect()->route('bs-subjects.index')->with('success', 'Subject deleted successfully.');
    }
}
