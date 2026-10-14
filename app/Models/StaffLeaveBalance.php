<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StaffLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id', 'leave_type_id', 'year', 'allocated_days', 'used_days', 'pending_days'
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
