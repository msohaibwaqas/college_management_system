<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class StaffAcademicQualification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'staff_academic_qualifications';

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
        'degree_level',
        'subject',
        'group_field',
        'total_marks',
        'obtained_marks',
        'degree_image',
        'is_verified',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'degree_level' => 'string',
        'subject' => 'string',
        'group_field' => 'string',
        'total_marks' => 'integer',
        'obtained_marks' => 'integer',
        'is_verified' => 'boolean',
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

    public function scopeForDegreeLevel(Builder $query, string $level): Builder
    {
        return $query->where('degree_level', $level);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeWithMarks(Builder $query): Builder
    {
        return $query->whereNotNull('total_marks');
    }

    public function scopeSearchBySubject(Builder $query, string $subject): Builder
    {
        return $query->where('subject', 'like', "%{$subject}%");
    }

    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query->where('group_field', $group);
    }

    // Accessors
    public function getPercentageAttribute(): float
    {
        if (!$this->total_marks || $this->total_marks == 0) {
            return 0.0;
        }
        return round(($this->obtained_marks / $this->total_marks) * 100, 2);
    }

    public function getGradeAttribute(): string
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 80) return 'A+';
        if ($percentage >= 70) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->percentage >= 40;
    }

    public function getDivisionAttribute(): string
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 75) return 'First Division';
        if ($percentage >= 60) return 'Second Division';
        if ($percentage >= 45) return 'Third Division';
        return 'Fail';
    }

    public function getFormattedDegreeLevelAttribute(): string
    {
        $levels = [
            'ssc' => 'SSC',
            'hssc' => 'HSSC',
            'bachelor' => 'Bachelor',
            'master' => 'Master',
            'bs' => 'BS',
            'mphil' => 'MPhil',
            'phd' => 'PhD',
            'postdoc' => 'PostDoc',
        ];
        
        return $levels[$this->degree_level] ?? ucfirst($this->degree_level);
    }

    public function getHasValidMarksAttribute(): bool
    {
        return $this->total_marks !== null && 
               $this->obtained_marks !== null && 
               $this->obtained_marks <= $this->total_marks &&
               $this->obtained_marks >= 0;
    }

    public function getDegreeImageUrlAttribute(): string
    {
        if ($this->degree_image) {
            return asset('storage/' . $this->degree_image);
        }
        return asset('images/default-degree.jpg');
    }

    // Mutators
    public function setSubjectAttribute($value): void
    {
        $this->attributes['subject'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setGroupFieldAttribute($value): void
    {
        $this->attributes['group_field'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setTotalMarksAttribute($value): void
    {
        $this->attributes['total_marks'] = $value !== '' ? (int)$value : null;
    }

    public function setObtainedMarksAttribute($value): void
    {
        $this->attributes['obtained_marks'] = $value !== '' ? (int)$value : null;
    }

    // Custom methods
    public function hasValidMarks(): bool
    {
        return $this->has_valid_marks;
    }

    public function getMarksSummary(): array
    {
        return [
            'total_marks' => $this->total_marks,
            'obtained_marks' => $this->obtained_marks,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'division' => $this->division,
            'is_passed' => $this->is_passed,
            'is_verified' => $this->is_verified,
        ];
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'degree_level' => $this->degree_level,
            'formatted_degree_level' => $this->formatted_degree_level,
            'subject' => $this->subject,
            'group_field' => $this->group_field,
            'marks_summary' => $this->getMarksSummary(),
            'degree_image' => $this->degree_image,
            'degree_image_url' => $this->degree_image_url,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getAcademicLevel(): int
    {
        $levels = [
            'ssc' => 1,
            'hssc' => 2,
            'bachelor' => 3,
            'master' => 4,
            'bs' => 5,
            'mphil' => 6,
            'phd' => 7,
            'postdoc' => 8,
        ];
        
        return $levels[$this->degree_level] ?? 0;
    }

    public function isHigherThan(string $level): bool
    {
        return $this->getAcademicLevel() > $this->getAcademicLevelForLevel($level);
    }

    public function isLowerThan(string $level): bool
    {
        return $this->getAcademicLevel() < $this->getAcademicLevelForLevel($level);
    }

    private function getAcademicLevelForLevel(string $level): int
    {
        $levels = [
            'ssc' => 1,
            'hssc' => 2,
            'bachelor' => 3,
            'master' => 4,
            'bs' => 5,
            'mphil' => 6,
            'phd' => 7,
            'postdoc' => 8,
        ];
        
        return $levels[$level] ?? 0;
    }
}
