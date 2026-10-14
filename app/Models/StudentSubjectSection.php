<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentSubjectSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_subject_sections';

    protected $fillable = [
        'student_id',
        'subject_id',
        'admission_year',
        'section',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'admission_year' => 'integer',
    ];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ─── Helper: upsert a single student's section for a subject/year ───────

    public static function assignSection(int $studentId, int $subjectId, int $year, string $section, ?int $userId = null): self
    {
        return self::updateOrCreate(
            [
                'student_id'     => $studentId,
                'subject_id'     => $subjectId,
                'admission_year' => $year,
            ],
            [
                'section'    => strtoupper(trim($section)),
                'updated_by' => $userId,
                'created_by' => $userId,
                'deleted_at' => null,   // restore if soft-deleted
            ]
        );
    }
}
