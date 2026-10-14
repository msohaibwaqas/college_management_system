<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'room_id',
        'staff_id',
        'admission_year',
        'subject_group_id',
        'day_of_week',
        'period_number',
        'start_time',
        'end_time',
        'is_break',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_break' => 'boolean',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper method to get period times
    public static function getPeriodTimes()
    {
        return [
            1 => ['start' => '09:00', 'end' => '09:45'],
            2 => ['start' => '09:45', 'end' => '10:30'],
            3 => ['start' => '10:30', 'end' => '11:15'],
            4 => ['start' => '11:15', 'end' => '12:00'],
            5 => ['start' => '12:00', 'end' => '12:45'],
            6 => ['start' => '12:45', 'end' => '13:30'],
            7 => ['start' => '13:30', 'end' => '14:15'],
        ];
    }

    public static function getDaysOfWeek()
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }
}
