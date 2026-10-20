<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StudentStruckOffLog extends Model
{
    protected $table = 'student_struck_off_logs';

    protected $fillable = [
        'student_id',
        'admission_id',
        'struck_off_by',
        'subject_id',
        'absence_count_at_strike',
        'struck_off_at',
        'struck_off_reason',
        'readmitted_by',
        'readmitted_at',
        'readmission_note',
        'academic_year',
        'status',
    ];

    protected $casts = [
        'struck_off_at'  => 'datetime',
        'readmitted_at'  => 'datetime',
        'academic_year'  => 'integer',
        'absence_count_at_strike' => 'integer',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admissions::class, 'admission_id');
    }

    /** The teacher (staff) who struck off the student */
    public function struckOffBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'struck_off_by');
    }

    /** The subject in which the absence threshold was crossed */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /** The admin/clerk who readmitted the student */
    public function readmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'readmitted_by');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'struck_off');
    }

    public function scopeReadmitted(Builder $query): Builder
    {
        return $query->where('status', 'readmitted');
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    public function isStruckOff(): bool
    {
        return $this->status === 'struck_off';
    }

    public function isReadmitted(): bool
    {
        return $this->status === 'readmitted';
    }

    /**
     * Get the number of times this student has been readmitted in the log's academic year.
     */
    public function getReadmissionsCountForYear(): int
    {
        return static::where('student_id', $this->student_id)
            ->where('academic_year', $this->academic_year)
            ->where('status', 'readmitted')
            ->count();
    }

    /**
     * Check if this log entry can be readmitted according to max allowed limit.
     */
    public function canBeReadmitted(int $maxAllowed): bool
    {
        return $this->isStruckOff() && ($this->getReadmissionsCountForYear() < $maxAllowed);
    }
}
