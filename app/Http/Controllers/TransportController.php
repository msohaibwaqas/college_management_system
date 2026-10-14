<?php

namespace App\Http\Controllers;

use App\Models\TransportRoute;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Student;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    public function index()
    {
        $routes = TransportRoute::with(['vehicle', 'driver'])->withCount('assignments')->get();
        return view('admin.transport.index', compact('routes'));
    }

    public function create()
    {
        $vehicles = Vehicle::where('status', 'active')->get();
        $drivers = Driver::all();
        return view('admin.transport.create', compact('vehicles', 'drivers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'start_point' => 'required',
            'end_point' => 'required',
            'monthly_fee' => 'required|numeric',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        TransportRoute::create($data);
        return redirect()->route('transport.index')->with('success', 'Route created successfully.');
    }

    public function assignStudent(Request $request)
    {
        // This would link student to route via pivot/model StudentTransport
        // and trigger fee voucher generation logic.
    }
}
