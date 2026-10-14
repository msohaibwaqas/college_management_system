<?php

namespace App\Http\Controllers;

use App\Models\SubjectAssignmentRule;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectAssignmentRuleController extends Controller
{
    public function index()
    {
        $rules = SubjectAssignmentRule::with('subjects')->get();
        return view('subject-rules.index', compact('rules'));
    }

    public function create()
    {
        $subjects = Subject::where('type', 'Compulsory')->get();
        return view('subject-rules.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_level' => 'nullable|string',
            'nationality_type' => 'required|in:pakistani,non_pakistani,any',
            'religion_type' => 'required|in:muslim,non_muslim,any',
            'assignment_type' => 'required|in:compulsory,elective',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id'
        ]);

        $rule = SubjectAssignmentRule::create([
            'class_level' => $validated['class_level'] ?? '11',
            'nationality_type' => $validated['nationality_type'],
            'religion_type' => $validated['religion_type'],
            'assignment_type' => $validated['assignment_type'],
        ]);

        $rule->subjects()->sync($validated['subjects']);

        return redirect()->route('subject-rules.index')->with('success', 'Rule created successfully.');
    }

    public function edit(SubjectAssignmentRule $subjectRule)
    {
        $subjects = Subject::where('type', 'Compulsory')->get();
        return view('subject-rules.edit', compact('subjectRule', 'subjects'));
    }

    public function update(Request $request, SubjectAssignmentRule $subjectRule)
    {
        $validated = $request->validate([
            'class_level' => 'nullable|string',
            'nationality_type' => 'required|in:pakistani,non_pakistani,any',
            'religion_type' => 'required|in:muslim,non_muslim,any',
            'assignment_type' => 'required|in:compulsory,elective',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id'
        ]);

        $subjectRule->update([
            'class_level' => $validated['class_level'] ?? '11',
            'nationality_type' => $validated['nationality_type'],
            'religion_type' => $validated['religion_type'],
            'assignment_type' => $validated['assignment_type'],
        ]);

        $subjectRule->subjects()->sync($validated['subjects']);

        return redirect()->route('subject-rules.index')->with('success', 'Rule updated successfully.');
    }

    public function destroy(SubjectAssignmentRule $subjectRule)
    {
        $subjectRule->delete();
        return redirect()->route('subject-rules.index')->with('success', 'Rule deleted successfully.');
    }
}
