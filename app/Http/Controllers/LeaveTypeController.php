<?php

namespace App\Http\Controllers;

use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            abort(403, 'Unauthorized access to leave types management.');
        }

        $leaveTypes = LeaveType::orderBy('name')->get();
        return view('staff.leaves.types.index', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            abort(403, 'Unauthorized access to create leave types.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name',
            'code' => 'required|string|max:20|unique:leave_types,code',
            'default_days' => 'required|integer|min:0',
            'max_leaves_per_month' => 'required|integer|min:1',
            'special_case_max_leaves_per_month' => 'required|integer|min:1|gte:max_leaves_per_month',
            'period_start_date' => 'nullable|date',
            'period_end_date' => 'nullable|date|after_or_equal:period_start_date',
            'is_paid' => 'boolean',
            'requires_document' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_paid'] = $request->has('is_paid');
        $validated['requires_document'] = $request->has('requires_document');
        $validated['is_active'] = true;
        $validated['created_by'] = Auth::id();

        LeaveType::create($validated);

        return redirect()->back()->with('success', 'Leave Type created successfully with monthly and period limits!');
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $user = Auth::user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            abort(403, 'Unauthorized access to edit leave types.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:leave_types,name,' . $leaveType->id,
            'code' => 'required|string|max:20|unique:leave_types,code,' . $leaveType->id,
            'default_days' => 'required|integer|min:0',
            'max_leaves_per_month' => 'required|integer|min:1',
            'special_case_max_leaves_per_month' => 'required|integer|min:1|gte:max_leaves_per_month',
            'period_start_date' => 'nullable|date',
            'period_end_date' => 'nullable|date|after_or_equal:period_start_date',
            'is_paid' => 'boolean',
            'requires_document' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $validated['is_paid'] = $request->has('is_paid');
        $validated['requires_document'] = $request->has('requires_document');
        $validated['updated_by'] = Auth::id();

        $leaveType->update($validated);

        return redirect()->back()->with('success', 'Leave Type limits updated successfully!');
    }

    public function destroy(LeaveType $leaveType)
    {
        $user = Auth::user();
        if (!$user->is_super_admin && !$user->hasRole('admin') && !$user->hasRole('principal')) {
            abort(403, 'Unauthorized access to delete leave types.');
        }

        $leaveType->delete();

        return redirect()->back()->with('success', 'Leave Type removed successfully.');
    }
}
