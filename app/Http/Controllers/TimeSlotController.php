<?php

namespace App\Http\Controllers;

use App\Models\TimeSlot;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function index()
    {
        $timeSlots = TimeSlot::orderBy('slot_number')->get();
        return view('admin.time_slots.index', compact('timeSlots'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'slot_number' => 'required|integer|unique:time_slots,slot_number',
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        TimeSlot::create($request->all());

        return redirect()->route('time-slots.index')->with('success', 'Time slot created successfully.');
    }

    public function edit(TimeSlot $timeSlot)
    {
        return view('admin.time_slots.edit', compact('timeSlot'));
    }

    public function update(Request $request, TimeSlot $timeSlot)
    {
        $request->validate([
            'slot_number' => 'required|integer|unique:time_slots,slot_number,' . $timeSlot->id,
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $timeSlot->update($request->all());

        return redirect()->route('time-slots.index')->with('success', 'Time slot updated successfully.');
    }

    public function destroy(TimeSlot $timeSlot)
    {
        if ($timeSlot->entries()->count() > 0) {
            return back()->with('error', 'Cannot delete time slot because it has associated timetable entries.');
        }

        $timeSlot->delete();

        return redirect()->route('time-slots.index')->with('success', 'Time slot deleted successfully.');
    }
}
