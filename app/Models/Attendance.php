<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'student_id',
        'subject_id',
        'staff_id',
        'attendance_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
