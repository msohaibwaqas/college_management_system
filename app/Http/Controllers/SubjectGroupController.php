<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubjectGroupController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $groups = SubjectGroup::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->withCount('subjects')
            ->orderBy('name')
            ->paginate(10);

        return view('subject_groups.index', compact('groups', 'search'));
    }

    public function create()
    {
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        return view('subject_groups.create', compact('subjects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:subject_groups,name',
            'description' => 'nullable|string',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id',
            'mandatory_subjects' => 'nullable|array',
        ]);

        $group = SubjectGroup::create($request->only('name', 'description'));
        
        $syncData = [];
        foreach ($request->subjects as $subjectId) {
            $slot = $request->input('slot_assignment_' . $subjectId, 0);
            $syncData[$subjectId] = [
                'is_mandatory' => is_array($request->mandatory_subjects) && in_array($subjectId, $request->mandatory_subjects),
                'is_slot1' => $slot == 1,
                'is_slot2' => $slot == 2,
                'is_slot3' => $slot == 3,
            ];
        }
        $group->subjects()->attach($syncData);

        Cache::forget('subject_groups_all');

        return redirect()->route('subject-groups.index')->with('success', 'Subject Group created successfully.');
    }

    public function edit(SubjectGroup $subjectGroup)
    {
        $subjects = Subject::where('type', 'Elective')->orderBy('name')->get();
        $selectedSubjects = $subjectGroup->subjects()->pluck('subjects.id')->toArray();
        $mandatorySubjects = $subjectGroup->subjects()->wherePivot('is_mandatory', true)->pluck('subjects.id')->toArray();
        $slot1Subjects = $subjectGroup->subjects()->wherePivot('is_slot1', true)->pluck('subjects.id')->toArray();
        $slot2Subjects = $subjectGroup->subjects()->wherePivot('is_slot2', true)->pluck('subjects.id')->toArray();
        $slot3Subjects = $subjectGroup->subjects()->wherePivot('is_slot3', true)->pluck('subjects.id')->toArray();
        
        return view('subject_groups.edit', compact(
            'subjectGroup', 'subjects', 'selectedSubjects', 
            'mandatorySubjects', 'slot1Subjects', 'slot2Subjects', 'slot3Subjects'
        ));
    }

    public function update(Request $request, SubjectGroup $subjectGroup)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:subject_groups,name,' . $subjectGroup->id,
            'description' => 'nullable|string',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,id',
            'mandatory_subjects' => 'nullable|array',
        ]);

        $subjectGroup->update($request->only('name', 'description'));
        
        $syncData = [];
        foreach ($request->subjects as $subjectId) {
            $slot = $request->input('slot_assignment_' . $subjectId, 0);
            $syncData[$subjectId] = [
                'is_mandatory' => is_array($request->mandatory_subjects) && in_array($subjectId, $request->mandatory_subjects),
                'is_slot1' => $slot == 1,
                'is_slot2' => $slot == 2,
                'is_slot3' => $slot == 3,
            ];
        }
        $subjectGroup->subjects()->sync($syncData);

        Cache::forget('subject_groups_all');

        return redirect()->route('subject-groups.index')->with('success', 'Subject Group updated successfully.');
    }

    public function destroy(SubjectGroup $subjectGroup)
    {
        $subjectGroup->delete();
        Cache::forget('subject_groups_all');
        return redirect()->route('subject-groups.index')->with('success', 'Subject Group deleted successfully.');
    }

    public function show(SubjectGroup $subjectGroup)
    {
        $subjectGroup->load('subjects');
        return view('subject_groups.show', compact('subjectGroup'));
    }
}
