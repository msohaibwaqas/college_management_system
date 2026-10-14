<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeTeacherAssignment extends Model
{
    use HasFactory;

    protected $table = 'he_teacher_assignments';

    protected $fillable = [
        'he_admission_id',
        'bs_subject_id',
        'staff_id',
        'academic_year',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'academic_year' => 'integer',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeForTeacher($query, int $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('bs_subject_id', $subjectId);
    }

    public function scopeForAcademicYear($query, int $year)
    {
        return $query->where('academic_year', $year);
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
