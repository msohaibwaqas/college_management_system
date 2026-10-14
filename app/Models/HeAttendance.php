<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeAttendance extends Model
{
    use HasFactory;

    protected $table = 'he_attendances';

    protected $fillable = [
        'he_admission_id',
        'bs_subject_id',
        'staff_id',
        'attendance_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function heAdmission(): BelongsTo
    {
        return $this->belongsTo(HeAdmission::class, 'he_admission_id');
    }

    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    // Scopes
    public function scopeForDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('bs_subject_id', $subjectId);
    }

    public function scopeForTeacher($query, int $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // Accessors
    public function getStudentNameAttribute(): string
    {
        if ($this->heAdmission && $this->heAdmission->student) {
            $student = $this->heAdmission->student;
            return trim($student->first_name . ' ' . ($student->last_name ?? ''));
        }
        return 'Unknown Student';
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->bsSubject ? $this->bsSubject->name : 'Unknown Subject';
    }

    public function getTeacherNameAttribute(): string
    {
        if ($this->staff) {
            return trim($this->staff->first_name . ' ' . ($this->staff->last_name ?? ''));
        }
        return 'Unknown Teacher';
    }
}
