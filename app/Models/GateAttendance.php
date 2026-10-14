<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GateAttendance extends Model
{
    protected $fillable = [
        'student_id',
        'date',
        'in_time',
        'out_time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}
