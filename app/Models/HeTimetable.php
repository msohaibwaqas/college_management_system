<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeTimetable extends Model
{
    use HasFactory;

    protected $table = 'he_timetables';

    protected $fillable = [
        'program_id',
        'semester_no',
        'admission_year',
        'bs_subject_id',
        'room_id',
        'staff_id',
        'day_of_week',
        'period_number',
        'start_time',
        'end_time',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getPeriodTimes()
    {
        return [
            1 => ['start' => '08:30', 'end' => '09:30'],
            2 => ['start' => '09:30', 'end' => '10:30'],
            3 => ['start' => '10:30', 'end' => '11:30'],
            4 => ['start' => '11:30', 'end' => '12:30'],
            5 => ['start' => '12:30', 'end' => '13:30'],
            6 => ['start' => '13:30', 'end' => '14:30'],
        ];
    }

    public static function getDaysOfWeek()
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    }
}
