<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffDailyAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_daily_attendance';

    protected $fillable = [
        'staff_id', 'date', 'status', 'check_in_time', 'check_out_time', 
        'leave_application_id', 'remarks'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveApplication()
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}
