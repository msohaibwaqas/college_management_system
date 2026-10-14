<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class HeResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'he_results';

    protected $fillable = [
        'he_admission_id',
        'bs_subject_id',
        'semester_no',
        'exam_type',
        'theory_marks',
        'practical_marks',
        'total_marks',
        'is_absent',
        'is_locked',
        'locked_by',
        'locked_at',
        'academic_year',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'semester_no' => 'integer',
        'theory_marks' => 'integer',
        'practical_marks' => 'integer',
        'total_marks' => 'integer',
        'is_absent' => 'boolean',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'academic_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function heAdmission(): BelongsTo
    {
        return $this->belongsTo(HeAdmission::class, 'he_admission_id')->withTrashed();
    }

    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeForSemester(Builder $query, int $semester): Builder
    {
        return $query->where('semester_no', $semester);
    }

    public function scopeForExamType(Builder $query, string $type): Builder
    {
        return $query->where('exam_type', $type);
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    public function scopeForProgram(Builder $query, int $programId): Builder
    {
        return $query->whereHas('heAdmission', function ($q) use ($programId) {
            $q->where('program_id', $programId);
        });
    }

    // Accessors
    public function getTotalObtainedMarksAttribute(): int
    {
        return ($this->theory_marks ?? 0) + ($this->practical_marks ?? 0);
    }

    public function getGradeAttribute(): string
    {
        if ($this->is_absent) {
            return 'Absent';
        }

        $percentage = $this->percentage;

        if ($percentage >= 85) return 'A';
        if ($percentage >= 80) return 'A-';
        if ($percentage >= 75) return 'B+';
        if ($percentage >= 71) return 'B';
        if ($percentage >= 68) return 'B-';
        if ($percentage >= 64) return 'C+';
        if ($percentage >= 61) return 'C';
        if ($percentage >= 58) return 'C-';
        if ($percentage >= 54) return 'D+';
        if ($percentage >= 50) return 'D';
        return 'F';
    }

    public function getPercentageAttribute(): float
    {
        if ($this->total_marks === 0) {
            return 0.0;
        }
        return round(($this->total_obtained_marks / $this->total_marks) * 100, 2);
    }

    public function getGpaAttribute(): float
    {
        if ($this->is_absent) {
            return 0.0;
        }

        $percentage = $this->percentage;

        if ($percentage >= 85) return 4.0;
        if ($percentage >= 80) return 3.7;
        if ($percentage >= 75) return 3.3;
        if ($percentage >= 71) return 3.0;
        if ($percentage >= 68) return 2.7;
        if ($percentage >= 64) return 2.3;
        if ($percentage >= 61) return 2.0;
        if ($percentage >= 58) return 1.7;
        if ($percentage >= 54) return 1.3;
        if ($percentage >= 50) return 1.0;
        return 0.0;
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->percentage >= 50 && !$this->is_absent;
    }

    // Custom methods

    /**
     * Lock the result (marks cannot be edited by clerk/teacher)
     */
    public function lock(int $userId): bool
    {
        $this->is_locked = true;
        $this->locked_by = $userId;
        $this->locked_at = now();
        return $this->save();
    }

    /**
     * Unlock the result (only admin/principal can do this)
     */
    public function unlock(): bool
    {
        $this->is_locked = false;
        $this->locked_by = null;
        $this->locked_at = null;
        return $this->save();
    }

    /**
     * Check if the result can be edited by the given user
     */
    public function canBeEditedBy($user): bool
    {
        // Admin and Principal can always edit
        if ($user->hasAnyRole(['admin', 'principal'])) {
            return true;
        }

        // If locked, other roles cannot edit
        if ($this->is_locked) {
            return false;
        }

        // Clerk who created it can edit if not locked
        if ($user->hasAnyRole(['bs_clerk', 'clerk']) && !$this->is_locked) {
            return true;
        }

        // Teacher can edit their subject's marks if not locked
        if ($user->hasRole('teacher') && !$this->is_locked) {
            // Check if this teacher is assigned to this student-subject
            $staff = $user->staff;
            if ($staff) {
                return HeTeacherAssignment::where('he_admission_id', $this->he_admission_id)
                    ->where('bs_subject_id', $this->bs_subject_id)
                    ->where('staff_id', $staff->id)
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Get the student name via heAdmission
     */
    public function getStudentNameAttribute(): string
    {
        if ($this->heAdmission && $this->heAdmission->student) {
            $student = $this->heAdmission->student;
            return trim($student->first_name . ' ' . ($student->last_name ?? ''));
        }
        return 'Unknown Student';
    }

    /**
     * Get the subject name
     */
    public function getSubjectNameAttribute(): string
    {
        return $this->bsSubject ? $this->bsSubject->name : 'Unknown Subject';
    }
}
