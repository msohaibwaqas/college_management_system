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

    /**
     * Check if a teacher has a valid unused reminder for the given class, subject, date and section.
     */
    public static function hasUnused(
        ?int $staffId,
        int $subjectId,
        string $date,
        ?string $class = null,
        ?int $year = null,
        ?string $section = null
    ): bool {
        if (!$staffId) {
            return false;
        }

        $query = static::where('staff_id', $staffId)
            ->where('subject_id', $subjectId)
            ->where('attendance_date', $date)
            ->where('is_used', false);

        if (!empty($class)) {
            $query->where('class_level', (string) $class);
        }

        if (!empty($year)) {
            $query->where('admission_year', $year);
        }

        if (!empty($section)) {
            $query->where(function ($q) use ($section) {
                $q->whereNull('section')
                  ->orWhere('section', '')
                  ->orWhere('section', $section);
            });
        }

        return $query->exists();
    }

    /**
     * Mark matching reminders as used (one-time exception consumed).
     */
    public static function consume(
        ?int $staffId,
        int $subjectId,
        string $date,
        ?string $class = null,
        ?int $year = null,
        ?string $section = null
    ): int {
        if (!$staffId) {
            return 0;
        }

        $query = static::where('staff_id', $staffId)
            ->where('subject_id', $subjectId)
            ->where('attendance_date', $date)
            ->where('is_used', false);

        if (!empty($class)) {
            $query->where('class_level', (string) $class);
        }

        if (!empty($year)) {
            $query->where('admission_year', $year);
        }

        if (!empty($section)) {
            $query->where(function ($q) use ($section) {
                $q->whereNull('section')
                  ->orWhere('section', '')
                  ->orWhere('section', $section);
            });
        }

        return $query->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }
}
