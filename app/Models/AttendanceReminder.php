<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReminder extends Model
{
    protected $fillable = [
        'staff_id',
        'created_by',
        'subject_id',
        'class_level',
        'admission_year',
        'section',
        'attendance_date',
        'message',
        'is_used',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'attendance_date' => 'date',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
