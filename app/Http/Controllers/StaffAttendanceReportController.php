<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffDailyAttendance;
use App\Models\PublicHoliday;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceReportController extends Controller
{
    public function index()
    {
        return view('admin.hr.reports.attendance');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m'
        ]);

        $monthPeriod = Carbon::parse($request->input('month') . '-01');
        $daysInMonth = $monthPeriod->daysInMonth;
        
        $startDate = $monthPeriod->copy()->startOfMonth();
        $endDate = $monthPeriod->copy()->endOfMonth();

        // Fetch all active staff
        $staffMembers = Staff::where('status', 'active')->orderBy('first_name')->get();

        // Fetch holidays for the month
        $holidays = PublicHoliday::where('is_active', true)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // Fetch attendance for the month
        $attendanceData = StaffDailyAttendance::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('staff_id');

        // Build the grid
        $reportGrid = [];
        foreach ($staffMembers as $staff) {
            $staffRow = [
                'staff' => $staff,
                'days' => [],
                'summary' => [
                    'present' => 0,
                    'absent' => 0,
                    'late' => 0,
                    'half_day' => 0,
                    'leave' => 0,
                    'holiday' => 0
                ]
            ];

            $staffAttendance = $attendanceData->get($staff->id, collect())->keyBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = $monthPeriod->copy()->day($day)->format('Y-m-d');
                $isWeekend = $monthPeriod->copy()->day($day)->isSunday();
                
                $statusCode = '-';
                $isHoliday = $holidays->has($currentDate);

                if ($isHoliday) {
                    $statusCode = 'H';
                    $staffRow['summary']['holiday']++;
                } else if ($staffAttendance->has($currentDate)) {
                    $status = $staffAttendance->get($currentDate)->status;
                    if ($status == 'present') { $statusCode = 'P'; $staffRow['summary']['present']++; }
                    elseif ($status == 'absent') { $statusCode = 'A'; $staffRow['summary']['absent']++; }
                    elseif ($status == 'late') { $statusCode = 'L'; $staffRow['summary']['late']++; }
                    elseif ($status == 'half_day') { $statusCode = 'HD'; $staffRow['summary']['half_day']++; }
                    elseif ($status == 'leave') { $statusCode = 'LV'; $staffRow['summary']['leave']++; }
                } else if ($isWeekend) {
                    $statusCode = 'W'; // Weekend
                }

                $staffRow['days'][$day] = $statusCode;
            }

            $reportGrid[] = $staffRow;
        }

        return view('admin.hr.reports.attendance', [
            'month' => $monthPeriod->format('F Y'),
            'monthValue' => $request->input('month'),
            'daysInMonth' => $daysInMonth,
            'reportGrid' => $reportGrid
        ]);
    }
}
