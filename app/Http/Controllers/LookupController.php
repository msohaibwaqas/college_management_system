<?php

namespace App\Http\Controllers;

use App\Models\BiseRecord;
use App\Models\BloodGroup;
use App\Models\Quota;
use App\Models\Subject;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    public function biseindex(Request $request)
    {
        $search = $request->input('search');
        $bises = BiseRecord::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10);

        return view('bise.index', compact('bises', 'search'));
    }

    public function bisestore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:bise_records,name',
        ]);

        BiseRecord::create($request->only('name'));
        return redirect()->back()->with('success', 'BISE added successfully.');
    }

    public function biseupdate(Request $request, BiseRecord $bise)
    {
        $request->validate([
            'name' => 'required|string|max:150|unique:bise_records,name,' . $bise->id,
        ]);

        $bise->update($request->only('name'));
        return redirect()->back()->with('success', 'BISE updated successfully.');
    }

    public function bisedestroy(BiseRecord $bise)
    {
        $bise->delete();
        return redirect()->back()->with('success', 'BISE deleted successfully.');
    }
    ////////////////////////////////////////////////////////////////////////////////////
    public function bloodGroupIndex(Request $request)
    {
        $search = $request->input('search');
        $bloodGroups = BloodGroup::query()
            ->when($search, fn($q) => $q->searchByCode($search))
            ->orderBy('code')
            ->paginate(10);

        return view('blood_group.index', compact('bloodGroups', 'search'));
    }

    // Store new blood group
    public function bloodGroupStore(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:blood_groups,code',
        ]);

        BloodGroup::create($request->only('code'));
        return redirect()->back()->with('success', 'Blood Group added successfully.');
    }

    // Update existing blood group
    public function bloodGroupUpdate(Request $request, BloodGroup $bloodGroup)
    {
        $request->validate([
            'code' => 'required|string|max:5|unique:blood_groups,code,' . $bloodGroup->id,
        ]);

        $bloodGroup->update($request->only('code'));
        return redirect()->back()->with('success', 'Blood Group updated successfully.');
    }

    // Delete blood group
    public function bloodGroupDestroy(BloodGroup $bloodGroup)
    {
        $bloodGroup->delete();
        return redirect()->back()->with('success', 'Blood Group deleted successfully.');
    }
    /////////////////////////////////////////////////////////////////////////////////////////
    // Display all quotas with search & pagination
    public function quotaIndex(Request $request)
    {
        $search = $request->input('search');
        $quotas = Quota::query()
            ->when($search, fn($q) => $q->searchByName($search))
            ->orderBy('name')
            ->paginate(10);

        return view('quota.index', compact('quotas', 'search'));
    }

    // Store new quota
    public function quotaStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:quotas,name',
        ]);

        Quota::create($request->only('name'));
        return redirect()->back()->with('success', 'Quota added successfully.');
    }

    // Update existing quota
    public function quotaUpdate(Request $request, Quota $quota)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:quotas,name,' . $quota->id,
        ]);

        $quota->update($request->only('name'));
        return redirect()->back()->with('success', 'Quota updated successfully.');
    }

    // Delete quota
    public function quotaDestroy(Quota $quota)
    {
        $quota->delete();
        return redirect()->back()->with('success', 'Quota deleted successfully.');
    }
    //////////////////////////////////////////////////////////////////////////////////////
    public function subjectIndex(Request $request)
    {
        $searchName = $request->input('search_name');
        $searchCode = $request->input('search_code');
        
        $subjects = Subject::query()
            ->when($searchName, fn($q) => $q->searchByName($searchName))
            ->when($searchCode, fn($q) => $q->searchByCode($searchCode))
            ->orderBy('name')
            ->paginate(10);

        return view('subject.index', compact('subjects', 'searchName', 'searchCode'));
    }

    /* ============================
    CREATE FORM
    ============================ */
    public function subjectCreate()
    {
        return view('subject.create');
    }

    /* ============================
    STORE SUBJECT
    ============================ */
    // Store new subject
    public function subjectStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:10|unique:subjects,code',
            'type' => 'required|in:Compulsory,Elective',
            'class_level' => 'required|in:11,12,both',
            // total_11 validation
            'total_11' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if (in_array($request->class_level, ['11', 'both']) && ($value === null || $value === '')) {
                        $fail('The Total Marks (Class 11) field is required for class 11 or both.');
                    }
                }
            ],
            // theory_12 validation
            'theory_12' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if (in_array($request->class_level, ['12', 'both']) && ($value === null || $value === '')) {
                        $fail('The Theory Marks (Class 12) field is required for class 12 or both.');
                    }
                }
            ],
            // practical_12 validation
            'practical_12' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->class_level === 'both' && $value === null) {
                        $fail('The Practical Marks (Class 12) field is required for class both.');
                    }
                }
            ],
        ]);

        // Prepare data with defaults
        $data = $request->only([
            'name', 'code', 'type', 'class_level', 'total_11', 'theory_12', 'practical_12'
        ]);

        // Set defaults based on class level
        if ($data['class_level'] === '11') {
            $data['theory_12'] = 0;
            $data['practical_12'] = 0;
        } elseif ($data['class_level'] === '12') {
            $data['total_11'] = 0;
            $data['practical_12'] = 0;
        } elseif ($data['class_level'] === 'both') {
            $data['total_11'] = $data['total_11'] ?? 0;
            $data['theory_12'] = $data['theory_12'] ?? 0;
            $data['practical_12'] = $data['practical_12'] ?? 0;
        }

        Subject::create($data);

        return redirect()->route('subject.index')->with('success', 'Subject created successfully.');
    }

    // Update existing subject
        /* ============================
                  UPDATE SUBJECT
        ============================ */
    public function subjectUpdate(Request $request, Subject $subject)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:10|unique:subjects,code,' . $subject->id,
            'type' => 'required|in:Compulsory,Elective',
            'class_level' => 'required|in:11,12,both',
            'total_11' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if (in_array($request->class_level, ['11', 'both']) && ($value === null || $value === '')) {
                        $fail('The Total Marks (Class 11) field is required for class 11 or both.');
                    }
                }
            ],
            'theory_12' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if (in_array($request->class_level, ['12', 'both']) && ($value === null || $value === '')) {
                        $fail('The Theory Marks (Class 12) field is required for class 12 or both.');
                    }
                }
            ],
            'practical_12' => [
                'nullable',
                'integer',
                'min:0',
                function($attribute, $value, $fail) use ($request) {
                    if ($request->class_level === 'both' && $value === null) {
                        $fail('The Practical Marks (Class 12) field is required for class both.');
                    }
                }
            ],
        ]);

        // Prepare data with defaults
        $data = $request->only([
            'name', 'code', 'type', 'class_level', 'total_11', 'theory_12', 'practical_12'
        ]);

        // Set defaults based on class level
        if ($data['class_level'] === '11') {
            $data['theory_12'] = 0;
            $data['practical_12'] = 0;
        } elseif ($data['class_level'] === '12') {
            $data['total_11'] = 0;
            $data['practical_12'] = 0;
        } elseif ($data['class_level'] === 'both') {
            $data['total_11'] = $data['total_11'] ?? 0;
            $data['theory_12'] = $data['theory_12'] ?? 0;
            $data['practical_12'] = $data['practical_12'] ?? 0;
        }

        $subject->update($data);

        return redirect()->route('subject.index')->with('success', 'Subject updated successfully.');
    }

    /* ============================
    EDIT FORM
    ============================ */
    public function subjectEdit(Subject $subject)
    {
        return view('subject.edit', compact('subject'));
    }

    /* ============================
    DELETE
    ============================ */
    public function subjectDestroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('subject.index')->with('success', 'Subject deleted successfully.');
    }

    /* ============================
    SHOW
    ============================ */
    public function subjectShow(Subject $subject)
    {
        return view('subject.show', compact('subject'));
    }


}
