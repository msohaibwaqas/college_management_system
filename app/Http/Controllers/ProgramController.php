<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programs = \App\Models\Program::all();
        return view('he.programs.index', compact('programs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('he.programs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:BS,ADP',
            'total_semesters' => 'required|integer|min:1|max:10',
        ]);

        $validated['created_by'] = auth()->id();

        \App\Models\Program::create($validated);

        return redirect()->route('programs.index')->with('success', 'Program created successfully.');
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
    public function edit(\App\Models\Program $program)
    {
        return view('he.programs.edit', compact('program'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, \App\Models\Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:BS,ADP',
            'total_semesters' => 'required|integer|min:1|max:10',
        ]);

        $validated['updated_by'] = auth()->id();

        $program->update($validated);

        return redirect()->route('programs.index')->with('success', 'Program updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Program $program)
    {
        $program->update(['deleted_by' => auth()->id()]);
        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Program deleted successfully.');
    }
}
