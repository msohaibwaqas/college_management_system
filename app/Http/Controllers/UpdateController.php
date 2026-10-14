<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function index()
    {
        $updates = \App\Models\Update::orderBy('date', 'desc')->paginate(10);
        return view('admin.updates.index', compact('updates'));
    }

    public function create()
    {
        return view('admin.updates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'date' => 'required|date',
            'type' => 'required|integer|in:1,2,3',
            'category' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|boolean',
        ]);

        $data = $request->except(['_token']);
        $data['uploaded_by'] = auth()->id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('updates', 'public');
            $data['image'] = $path;
        }

        \App\Models\Update::create($data);

        return redirect()->route('updates.index')->with('success', 'Update created successfully.');
    }

    public function show($id)
    {
        $update = \App\Models\Update::findOrFail($id);
        return view('admin.updates.show', compact('update'));
    }

    public function edit($id)
    {
        $update = \App\Models\Update::findOrFail($id);
        return view('admin.updates.edit', compact('update'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'date' => 'required|date',
            'type' => 'required|integer|in:1,2,3',
            'category' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:2048',
            'status' => 'required|boolean',
        ]);

        $update = \App\Models\Update::findOrFail($id);
        $data = $request->except(['_token', '_method']);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($update->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($update->image)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($update->image);
            }
            $path = $request->file('image')->store('updates', 'public');
            $data['image'] = $path;
        }

        $update->update($data);

        return redirect()->route('updates.index')->with('success', 'Update updated successfully.');
    }

    public function destroy($id)
    {
        $update = \App\Models\Update::findOrFail($id);
        if ($update->image && \Illuminate\Support\Facades\Storage::disk('public')->exists($update->image)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($update->image);
        }
        $update->delete();

        return redirect()->route('updates.index')->with('success', 'Update deleted successfully.');
    }
}
