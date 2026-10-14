<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    protected $table = 'timetable_entries';

    protected $fillable = [
        'subject_id',
        'staff_id',
        'section',
        'admission_year',
        'day_of_week',
        'time_slot_id',
        'room_id'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
