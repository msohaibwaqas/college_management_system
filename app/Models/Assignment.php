<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staff_id',
        'subject_id',
        'bs_subject_id',
        'title',
        'description',
        'file_path',
        'due_date',
        'total_marks',
        'target_group',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    /**
     * Get the teacher who created the assignment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get the Inter subject for the assignment.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the HE subject for the assignment.
     */
    public function bsSubject(): BelongsTo
    {
        return $this->belongsTo(BsSubject::class, 'bs_subject_id');
    }

    /**
     * Get unified subject name.
     */
    public function getSubjectNameAttribute(): string
    {
        if ($this->subject) return $this->subject->name;
        if ($this->bsSubject) return $this->bsSubject->name;
        return 'N/A';
    }

    /**
     * Get all submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Scope for upcoming assignments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('due_date', '>=', now());
    }
}
