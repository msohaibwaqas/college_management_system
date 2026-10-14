<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveApprovalLog;
use App\Models\StaffLeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $query = LeaveApplication::with(['staff', 'leaveType'])
            ->where('status', '!=', 'cancelled')
            ->orderByRaw("FIELD(status, 'pending', 'hod_approved', 'principal_approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $applications = $query->paginate(20);
        return view('admin.hr.leave_approvals.index', compact('applications', 'status'));
    }

    public function show($id)
    {
        $application = LeaveApplication::with(['staff', 'leaveType', 'logs.actionBy'])->findOrFail($id);
        
        // Fetch current balances for this staff
        $balances = StaffLeaveBalance::with('leaveType')
            ->where('staff_id', $application->staff_id)
            ->where('year', Carbon::parse($application->from_date)->year)
            ->get();

        return view('admin.hr.leave_approvals.show', compact('application', 'balances'));
    }

    public function updateStatus(Request $request, $id)
    {
        $application = LeaveApplication::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:hod_approved,principal_approved,rejected',
            'comments' => 'nullable|string|max:1000'
        ]);

        $newStatus = $validated['status'];
        $approverId = Auth::id();

        DB::beginTransaction();

        try {
            // Log the transition
            LeaveApprovalLog::create([
                'leave_application_id' => $application->id,
                'action_by_id' => $approverId,
                'action' => $newStatus,
                'comments' => $validated['comments']
            ]);

            // If final approval (Principal), deduct balance
            if ($newStatus === 'principal_approved' && $application->status !== 'principal_approved') {
                $balance = StaffLeaveBalance::firstOrCreate(
                    [
                        'staff_id' => $application->staff_id,
                        'leave_type_id' => $application->leave_type_id,
                        'year' => Carbon::parse($application->from_date)->year
                    ],
                    [
                        'allocated_days' => $application->leaveType->default_days ?? 25,
                        'pending_days' => $application->leaveType->default_days ?? 25,
                        'used_days' => 0
                    ]
                );
                
                if ($balance) {
                    if ($balance->pending_days < $application->total_days) {
                        $balance->pending_days = max(0, (float)$application->total_days);
                    }
                    $balance->pending_days -= $application->total_days;
                    $balance->used_days += $application->total_days;
                    $balance->save();
                }
            }

            // If reverting a principal approval (e.g. Rejecting an already approved leave)
            if ($newStatus === 'rejected' && $application->status === 'principal_approved') {
                $balance = StaffLeaveBalance::where('staff_id', $application->staff_id)
                    ->where('leave_type_id', $application->leave_type_id)
                    ->where('year', Carbon::parse($application->from_date)->year)
                    ->first();
                
                if ($balance) {
                    $balance->pending_days += $application->total_days;
                    $balance->used_days -= $application->total_days;
                    $balance->save();
                }
            }

            $application->update([
                'status' => $newStatus,
                'current_approver_id' => $approverId
            ]);

            DB::commit();

            return redirect()->route('leave-approvals.show', $application->id)
                ->with('success', 'Leave application status updated to ' . str_replace('_', ' ', strtoupper($newStatus)) . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update workflow state: ' . $e->getMessage());
        }
    }
}
