<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\Staff;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    public function index()
    {
        $hostels = Hostel::withCount('rooms')->get();
        return view('admin.hostel.index', compact('hostels'));
    }

    public function rooms($hostel_id)
    {
        $hostel = Hostel::findOrFail($hostel_id);
        $rooms = $hostel->rooms()->withCount('allotments')->get();
        return view('admin.hostel.rooms', compact('hostel', 'rooms'));
    }

    public function storeRoom(Request $request, $hostel_id)
    {
        $data = $request->validate([
            'room_number' => 'required',
            'type' => 'required|in:single,double,triple,dormitory',
            'capacity' => 'required|integer',
            'monthly_rent' => 'required|numeric',
        ]);

        $data['hostel_id'] = $hostel_id;
        HostelRoom::create($data);
        return back()->with('success', 'Room added successfully.');
    }
}
