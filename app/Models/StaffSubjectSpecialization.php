<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class StaffSubjectSpecialization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff_subject_specializations';

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'staff_id',
        'subject_id',
        'proficiency_level',
        'years_of_experience',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'proficiency_level' => 'string',
        'years_of_experience' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relationships
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    // Scopes
    public function scopeForStaff(Builder $query, int $staffId): Builder
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForProficiencyLevel(Builder $query, string $level): Builder
    {
        return $query->where('proficiency_level', $level);
    }

    public function scopeForExperienceRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('years_of_experience', [$min, $max]);
    }

    public function scopeExpert(Builder $query): Builder
    {
        return $query->where('proficiency_level', 'expert');
    }

    public function scopeIntermediate(Builder $query): Builder
    {
        return $query->where('proficiency_level', 'intermediate');
    }

    public function scopeBeginner(Builder $query): Builder
    {
        return $query->where('proficiency_level', 'beginner');
    }

    public function scopeSearchBySubjectName(Builder $query, string $name): Builder
    {
        return $query->join('subjects', 'staff_subject_specializations.subject_id', '=', 'subjects.id')
                    ->where('subjects.name', 'like', "%{$name}%")
                    ->select('staff_subject_specializations.*');
    }

    public function scopeSearchByStaffName(Builder $query, string $name): Builder
    {
        return $query->join('staff', 'staff_subject_specializations.staff_id', '=', 'staff.id')
                    ->where(function($q) use ($name) {
                        $q->where('staff.first_name', 'like', "%{$name}%")
                          ->orWhere('staff.last_name', 'like', "%{$name}%");
                    })
                    ->select('staff_subject_specializations.*');
    }

    // Accessors
    public function getProficiencyScoreAttribute(): int
    {
        $scores = [
            'beginner' => 1,
            'intermediate' => 2,
            'expert' => 3,
        ];
        
        return $scores[$this->proficiency_level] ?? 0;
    }

    public function getFormattedProficiencyLevelAttribute(): string
    {
        $levels = [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'expert' => 'Expert',
        ];
        
        return $levels[$this->proficiency_level] ?? ucfirst($this->proficiency_level);
    }

    public function getExperienceCategoryAttribute(): string
    {
        $years = $this->years_of_experience;
        
        if ($years < 2) return 'New';
        if ($years < 5) return 'Experienced';
        if ($years < 10) return 'Senior';
        return 'Veteran';
    }

    public function getOverallRatingAttribute(): float
    {
        // Weighted rating: 60% proficiency + 40% experience
        $proficiencyScore = $this->proficiency_score;
        $experienceScore = min($this->years_of_experience / 10, 3); // Cap at 3
        
        return round(($proficiencyScore * 0.6) + ($experienceScore * 0.4), 2);
    }

    public function getIsHighlyQualifiedAttribute(): bool
    {
        return $this->proficiency_level === 'expert' && $this->years_of_experience >= 5;
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->subject ? $this->subject->name : 'Unknown Subject';
    }

    public function getStaffNameAttribute(): string
    {
        return $this->staff ? $this->staff->first_name . ' ' . ($this->staff->last_name ?? '') : 'Unknown Staff';
    }

    // Mutators
    public function setProficiencyLevelAttribute($value): void
    {
        $this->attributes['proficiency_level'] = $value ?: 'intermediate';
    }

    public function setYearsOfExperienceAttribute($value): void
    {
        $this->attributes['years_of_experience'] = max(0, (int)$value);
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff_name,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject_name,
            'proficiency_level' => $this->proficiency_level,
            'formatted_proficiency_level' => $this->formatted_proficiency_level,
            'proficiency_score' => $this->proficiency_score,
            'years_of_experience' => $this->years_of_experience,
            'experience_category' => $this->experience_category,
            'overall_rating' => $this->overall_rating,
            'is_highly_qualified' => $this->is_highly_qualified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isQualifiedToTeach(): bool
    {
        return $this->proficiency_level !== 'beginner' || $this->years_of_experience > 0;
    }

    public function canHandleAdvancedTopics(): bool
    {
        return $this->proficiency_level === 'expert' && $this->years_of_experience >= 3;
    }

    public function getCompetencyLevel(): string
    {
        $rating = $this->overall_rating;
        
        if ($rating >= 2.5) return 'High Competency';
        if ($rating >= 1.5) return 'Medium Competency';
        return 'Low Competency';
    }

    public function getRecommendationStatus(): string
    {
        if ($this->is_highly_qualified) {
            return 'Highly Recommended';
        } elseif ($this->is_qualified_to_teach) {
            return 'Recommended';
        } else {
            return 'Not Recommended';
        }
    }

    public function getYearsToExpertLevel(): int
    {
        $targetYears = 5; // Assuming 5 years for expert level
        $currentYears = $this->years_of_experience;
        
        return $currentYears >= $targetYears ? 0 : ($targetYears - $currentYears);
    }
}
