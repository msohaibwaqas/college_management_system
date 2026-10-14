<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    /**
     * Display a listing of designations
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        
        $query = Designation::withCount([
            'staff as total_staff_count',
            'staff as active_staff_count' => function ($query) {
                $query->where('status', 'active');
            },
            'staff as left_staff_count' => function ($query) {
                $query->where('status', 'left');
            },
            'staff as transferred_staff_count' => function ($query) {
                $query->where('status', 'transferred');
            },
            'staff as retired_staff_count' => function ($query) {
                $query->where('status', 'retired');
            },
            'staff as suspended_staff_count' => function ($query) {
                $query->where('status', 'suspended');
            }
        ]);
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('bps', 'like', "%{$search}%");
        }
        
        $designations = $query->orderBy('name', 'asc')->paginate(20);
        
        return view('designations.index', compact('designations', 'search'));
    }

    /**
     * Store a newly created designation
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:designations,name',
            'bps' => 'required|integer|min:1|max:22',
        ]);

        Designation::create([
            'name' => $request->name,
            'bps' => $request->bps,
        ]);

        return redirect()->route('designations.index')->with('success', 'Designation added successfully!');
    }

    /**
     * Update the specified designation
     */
    public function update(Request $request, Designation $designation)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:designations,name,' . $designation->id,
            'bps' => 'required|integer|min:1|max:22',
        ]);

        $designation->update([
            'name' => $request->name,
            'bps' => $request->bps,
        ]);

        return redirect()->route('designations.index')->with('success', 'Designation updated successfully!');
    }

    /**
     * Remove the specified designation
     */
    public function destroy(Designation $designation)
    {
        // Check if designation is assigned to any staff
        if ($designation->staff()->count() > 0) {
            return redirect()->route('designations.index')
                ->with('error', 'Cannot delete designation. It is assigned to ' . $designation->staff()->count() . ' staff member(s).');
        }

        $designation->delete();

        return redirect()->route('designations.index')->with('success', 'Designation deleted successfully!');
    }
}
