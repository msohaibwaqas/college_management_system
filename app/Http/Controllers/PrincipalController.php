<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrincipalController extends Controller
{
    public function index()
    {
        $principals = \App\Models\Principal::orderBy('from_date', 'desc')->paginate(10);
        return view('admin.principals.index', compact('principals'));
    }

    public function create()
    {
        $staff = \App\Models\Staff::where('status', 'active')->orderBy('first_name')->get();
        return view('admin.principals.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'gender' => 'required|string|in:Male,Female,Other',
            'father_name' => 'required|string|max:100',
            'designation' => 'required|string|max:50',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'details' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_current' => 'required|boolean',
            'staff_id' => 'nullable|exists:staff,id',
            'status' => 'required|boolean',
        ]);

        $data = $request->except('_token');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('principals', 'public');
            $data['image'] = $path;
        }

        // If this principal is set as current, unset all other current principals
        if (isset($data['is_current']) && $data['is_current'] == 1) {
            \App\Models\Principal::where('is_current', 1)->update(['is_current' => 0]);
        }

        \App\Models\Principal::create($data);

        return redirect()->route('principals.index')->with('success', 'Principal record created successfully.');
    }

    public function show($id)
    {
        $principal = \App\Models\Principal::findOrFail($id);
        return view('admin.principals.edit', compact('principal'));
    }

    public function edit($id)
    {
        $principal = \App\Models\Principal::findOrFail($id);
        $staff = \App\Models\Staff::where('status', 'active')->orderBy('first_name')->get();
        return view('admin.principals.edit', compact('principal', 'staff'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:20',
            'name' => 'required|string|max:100',
            'gender' => 'required|string|in:Male,Female,Other',
            'father_name' => 'required|string|max:100',
            'designation' => 'required|string|max:50',
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'details' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_current' => 'required|boolean',
            'staff_id' => 'nullable|exists:staff,id',
            'status' => 'required|boolean',
        ]);

        $principal = \App\Models\Principal::findOrFail($id);
        $data = $request->except(['_token', '_method']);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($principal->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($principal->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($principal->image);
            }
            $path = $request->file('image')->store('principals', 'public');
            $data['image'] = $path;
        }

        // If this principal is set as current, unset all other current principals
        if (isset($data['is_current']) && $data['is_current'] == 1) {
            \App\Models\Principal::where('id', '!=', $id)->where('is_current', 1)->update(['is_current' => 0]);
        }

        $principal->update($data);

        return redirect()->route('principals.index')->with('success', 'Principal record updated successfully.');
    }

    public function destroy($id)
    {
        $principal = \App\Models\Principal::findOrFail($id);
        if ($principal->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($principal->image)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($principal->image);
        }
        $principal->delete();

        return redirect()->route('principals.index')->with('success', 'Principal record deleted successfully.');
    }
}
