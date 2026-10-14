<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommitteeController extends Controller
{
    public function index()
    {
        $committees = \App\Models\Committee::orderBy('order_by', 'asc')->paginate(10);
        return view('admin.committees.index', compact('committees'));
    }

    public function create()
    {
        return view('admin.committees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'order_by' => 'required|integer',
            'status' => 'required|boolean',
        ]);

        \App\Models\Committee::create($request->except('_token'));

        return redirect()->route('committees.index')->with('success', 'Committee created successfully.');
    }

    public function show($id)
    {
        // Not really needed for committees unless we add members later
        $committee = \App\Models\Committee::findOrFail($id);
        return view('admin.committees.edit', compact('committee'));
    }

    public function edit($id)
    {
        $committee = \App\Models\Committee::findOrFail($id);
        return view('admin.committees.edit', compact('committee'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'order_by' => 'required|integer',
            'status' => 'required|boolean',
        ]);

        $committee = \App\Models\Committee::findOrFail($id);
        $committee->update($request->except(['_token', '_method']));

        return redirect()->route('committees.index')->with('success', 'Committee updated successfully.');
    }

    public function destroy($id)
    {
        $committee = \App\Models\Committee::findOrFail($id);
        $committee->delete();

        return redirect()->route('committees.index')->with('success', 'Committee deleted successfully.');
    }
}
