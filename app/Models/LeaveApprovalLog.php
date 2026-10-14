<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApprovalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_application_id', 'action_by_id', 'action', 'comments'
    ];

    public function application()
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_id');
    }

    public function actionBy()
    {
        return $this->belongsTo(User::class, 'action_by_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'action_by_id');
    }
}
