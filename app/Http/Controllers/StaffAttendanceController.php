<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffDailyAttendance;
use App\Models\PublicHoliday;
use App\Models\LeaveApplication;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $parsedDate = Carbon::parse($date);
        
        $holiday = PublicHoliday::where('is_active', true)
                                ->whereDate('date', $parsedDate->toDateString())
                                ->first();

        // Get all active staff
        $staffMembers = Staff::where('status', 'active')->orderBy('id')->get();
        
        // Get existing attendance for this date
        $existingAttendance = StaffDailyAttendance::whereDate('date', $parsedDate->toDateString())
                                                  ->get()
                                                  ->keyBy('staff_id');

        // Check for approved leaves on this date
        $leaves = LeaveApplication::where('status', 'principal_approved') // Or whatever the final approval state is
                                  ->whereDate('from_date', '<=', $parsedDate->toDateString())
                                  ->whereDate('to_date', '>=', $parsedDate->toDateString())
                                  ->get()
                                  ->keyBy('staff_id');

        return view('admin.hr.attendance.index', compact('date', 'staffMembers', 'existingAttendance', 'holiday', 'leaves'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.status' => 'required|string|in:present,absent,late,half_day,leave,holiday',
            'attendance.*.check_in_time' => 'nullable|date_format:H:i',
            'attendance.*.check_out_time' => 'nullable|date_format:H:i',
            'attendance.*.remarks' => 'nullable|string',
            'attendance.*.leave_application_id' => 'nullable|exists:leave_applications,id'
        ]);

        $date = $validated['date'];

        foreach ($validated['attendance'] as $staffId => $data) {
            StaffDailyAttendance::updateOrCreate(
                ['staff_id' => $staffId, 'date' => $date],
                [
                    'status' => $data['status'],
                    'check_in_time' => $data['check_in_time'] ?? null,
                    'check_out_time' => $data['check_out_time'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'leave_application_id' => $data['leave_application_id'] ?? null,
                ]
            );
        }

        return redirect()->route('staff.attendance.index', ['date' => $date])
            ->with('success', 'Staff attendance updated successfully.');
    }
}
