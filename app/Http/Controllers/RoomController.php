<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::orderBy('name')->get();
        return view('rooms.index', compact('rooms'));
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:rooms,name|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'required|in:lecture,exam,both',
        ]);

        Room::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'location' => $request->location,
            'description' => $request->description,
            'purpose' => $request->purpose,
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('rooms.index')->with('success', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        return view('rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name,' . $room->id,
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'purpose' => 'required|in:lecture,exam,both',
        ]);

        $room->update([
            'name' => $request->name,
            'capacity' => $request->capacity,
            'location' => $request->location,
            'description' => $request->description,
            'purpose' => $request->purpose,
            'is_active' => $request->has('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }
}
