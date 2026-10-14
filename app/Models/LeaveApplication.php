<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id', 'leave_type_id', 'from_date', 'to_date', 'total_days', 
        'is_half_day', 'is_special_case', 'special_case_reason', 'reason', 'document_path', 'status', 'current_approver_id'
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'total_days' => 'decimal:1',
        'is_half_day' => 'boolean',
        'is_special_case' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function logs()
    {
        return $this->hasMany(LeaveApprovalLog::class);
    }

    public function currentApprover()
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }
}
