<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\LeaveType;
use App\Models\LeaveApplication;
use App\Models\StaffLeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MyLeaveController extends Controller
{
    private function getAuthenticatedStaff()
    {
        // Find staff by email matching the authenticated user
        return Staff::where('email', Auth::user()->email)->where('status', 'active')->first();
    }

    public function index()
    {
        $staff = $this->getAuthenticatedStaff();

        if (!$staff) {
            return redirect()->route('dashboard')->with('error', 'No active staff profile found for your account.');
        }

        $currentYear = Carbon::now()->year;

        // Ensure balances exist for this year (create default ones if not)
        $leaveTypes = LeaveType::where('is_active', true)->get();
        foreach ($leaveTypes as $type) {
            StaffLeaveBalance::firstOrCreate(
                ['staff_id' => $staff->id, 'leave_type_id' => $type->id, 'year' => $currentYear],
                ['allocated_days' => $type->default_days, 'pending_days' => $type->default_days, 'used_days' => 0]
            );
        }

        $balances = StaffLeaveBalance::with('leaveType')
            ->where('staff_id', $staff->id)
            ->where('year', $currentYear)
            ->get();

        $applications = LeaveApplication::with('leaveType', 'currentApprover')
            ->where('staff_id', $staff->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('staff.leaves.index', compact('balances', 'applications', 'currentYear'));
    }

    public function create()
    {
        $staff = $this->getAuthenticatedStaff();
        if (!$staff) return redirect()->route('dashboard')->with('error', 'No active staff profile found.');

        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('staff.leaves.create', compact('leaveTypes'));
    }

    public function store(Request $request)
    {
        $staff = $this->getAuthenticatedStaff();
        if (!$staff) return redirect()->route('dashboard')->with('error', 'No active staff profile found.');

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date' => 'required|date|after_or_equal:from_date',
            'is_half_day' => 'boolean',
            'is_special_case' => 'nullable|boolean',
            'special_case_reason' => 'required_if:is_special_case,1|nullable|string|min:5',
            'reason' => 'required|string|min:10',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048' // 2MB max
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        if ($leaveType->requires_document && !$request->hasFile('document')) {
            return back()->withErrors(['document' => 'A supporting document is required for this leave type.'])->withInput();
        }

        $fromDate = Carbon::parse($validated['from_date']);
        $toDate = Carbon::parse($validated['to_date']);
        $totalDays = $request->has('is_half_day') ? 0.5 : $fromDate->diffInDays($toDate) + 1;

        // Check Period Bounds
        if ($leaveType->period_start_date && $fromDate->lt($leaveType->period_start_date)) {
            return back()->withErrors(['from_date' => "Leaves for {$leaveType->name} can only be taken from " . $leaveType->period_start_date->format('d-m-Y') . "."])->withInput();
        }
        if ($leaveType->period_end_date && $toDate->gt($leaveType->period_end_date)) {
            return back()->withErrors(['to_date' => "Leaves for {$leaveType->name} cannot exceed " . $leaveType->period_end_date->format('d-m-Y') . "."])->withInput();
        }

        // Check Monthly Limits
        $isSpecialCase = $request->has('is_special_case') && $request->boolean('is_special_case');
        $maxAllowedMonthly = $isSpecialCase ? ($leaveType->special_case_max_leaves_per_month ?? 7) : ($leaveType->max_leaves_per_month ?? 2);

        $existingMonthlyDays = LeaveApplication::where('staff_id', $staff->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'cancelled')
            ->whereYear('from_date', $fromDate->year)
            ->whereMonth('from_date', $fromDate->month)
            ->sum('total_days');

        if (($existingMonthlyDays + $totalDays) > $maxAllowedMonthly) {
            $limitTypeLabel = $isSpecialCase ? "special case limit of {$maxAllowedMonthly} days" : "regular monthly limit of {$maxAllowedMonthly} days";
            $specialHint = !$isSpecialCase ? " (If this is a special situation, check 'Is Special Case?' for up to {$leaveType->special_case_max_leaves_per_month} days)." : "";
            return back()->withErrors([
                'leave_type_id' => "Exceeds {$limitTypeLabel} for {$leaveType->name} in " . $fromDate->format('F Y') . ". (Used/Requested: {$existingMonthlyDays} days, New Request: {$totalDays} days){$specialHint}"
            ])->withInput();
        }

        // Check Balance
        $currentYear = Carbon::now()->year;
        $balance = StaffLeaveBalance::where('staff_id', $staff->id)->where('leave_type_id', $leaveType->id)->where('year', $currentYear)->first();
        
        if ($balance && $balance->pending_days < $totalDays) {
            return back()->withErrors(['leave_type_id' => 'Insufficient annual leave balance for this type.'])->withInput();
        }

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('leaves/documents', 'public');
        }

        LeaveApplication::create([
            'staff_id' => $staff->id,
            'leave_type_id' => $leaveType->id,
            'from_date' => $validated['from_date'],
            'to_date' => $validated['to_date'],
            'total_days' => $totalDays,
            'is_half_day' => $request->has('is_half_day'),
            'is_special_case' => $isSpecialCase,
            'special_case_reason' => $isSpecialCase ? $request->input('special_case_reason') : null,
            'reason' => $validated['reason'],
            'document_path' => $documentPath,
            'status' => 'pending' // HOD or Principal needs to approve
        ]);

        return redirect()->route('my-leaves.index')->with('success', 'Leave application submitted successfully. It is now pending approval.');
    }
}
