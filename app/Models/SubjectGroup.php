<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubjectGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'subject_groups';
    protected $guarded = [];

    /**
     * The subjects that belong to the group.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'subject_group_subjects', 'subject_group_id', 'subject_id')
                    ->withPivot('is_mandatory', 'is_slot1', 'is_slot2', 'is_slot3')
                    ->withTimestamps();
    }

    /**
     * Get elective subjects only.
     */
    public function electiveSubjects()
    {
        return $this->subjects()->where('type', 'Elective');
    }
}
