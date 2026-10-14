<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index()
    {
        $holidays = PublicHoliday::orderBy('date', 'desc')->get();
        return view('admin.hr.holidays.index', compact('holidays'));
    }

    public function create()
    {
        return view('admin.hr.holidays.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date|unique:public_holidays,date',
            'type' => 'required|string|in:public,gazetted,local',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $request->has('is_active');

        PublicHoliday::create($validated);

        return redirect()->route('holidays.index')->with('success', 'Holiday created successfully.');
    }

    public function edit(PublicHoliday $holiday) // note parameter name must match route
    {
        return view('admin.hr.holidays.form', compact('holiday'));
    }

    public function update(Request $request, PublicHoliday $holiday)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date|unique:public_holidays,date,' . $holiday->id,
            'type' => 'required|string|in:public,gazetted,local',
            'is_active' => 'boolean',
            'description' => 'nullable|string'
        ]);

        $validated['is_active'] = $request->has('is_active');

        $holiday->update($validated);

        return redirect()->route('holidays.index')->with('success', 'Holiday updated successfully.');
    }

    public function destroy(PublicHoliday $holiday)
    {
        $holiday->delete();
        return redirect()->route('holidays.index')->with('success', 'Holiday removed successfully.');
    }
}
