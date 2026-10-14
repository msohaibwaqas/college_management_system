<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeDatesheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'he_datesheets';

    protected $fillable = [
        'program_id',
        'semester_no',
        'bs_subject_id',
        'exam_type',
        'exam_date',
        'start_time',
        'end_time',
        'academic_year',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'semester_no' => 'integer',
        'exam_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'academic_year' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id')->withTrashed();
    }

    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function seatingPlans(): HasMany
    {
        return $this->hasMany(HeSeatingPlan::class, 'he_datesheet_id');
    }

    // Scopes
    public function scopeForProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }

    public function scopeForSemester($query, int $semester)
    {
        return $query->where('semester_no', $semester);
    }

    public function scopeForExamType($query, string $type)
    {
        return $query->where('exam_type', $type);
    }

    public function scopeForAcademicYear($query, int $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>=', now()->toDateString());
    }

    // Accessors
    public function getProgramNameAttribute(): string
    {
        return $this->program ? $this->program->name : 'Unknown Program';
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->bsSubject ? $this->bsSubject->name : 'Unknown Subject';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->exam_date ? $this->exam_date->format('d M Y') : 'N/A';
    }

    public function getFormattedTimeAttribute(): string
    {
        $start = $this->start_time ? $this->start_time->format('h:i A') : '--';
        $end = $this->end_time ? $this->end_time->format('h:i A') : '--';
        return "$start - $end";
    }
}
