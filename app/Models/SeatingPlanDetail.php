<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatingPlanDetail extends Model
{
    protected $fillable = [
        'seating_plan_id',
        'room_id',
        'student_id',
        'subject_id',
        'seat_no',
        'admission_year'
    ];

    public function seatingPlan()
    {
        return $this->belongsTo(SeatingPlan::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getTeacherAttribute()
    {
        return \App\Models\StudentTeacherSubject::where('student_id', $this->student_id)
            ->where('subject_id', $this->subject_id)
            ->with('staff')
            ->first()
            ?->staff;
    }
}
