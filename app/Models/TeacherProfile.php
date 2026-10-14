<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class TeacherProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'teacher_profiles';

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
        'user_id',
        'employee_id',
        'qualification',
        'specialization',
        'joining_date',
        'experience_years',
        'bio',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'joining_date' => 'date',
        'experience_years' => 'integer',
        'bio' => 'string',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
    public function scopeForEmployeeId(Builder $query, string $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForQualification(Builder $query, string $qualification): Builder
    {
        return $query->where('qualification', 'like', "%{$qualification}%");
    }

    public function scopeForSpecialization(Builder $query, string $specialization): Builder
    {
        return $query->where('specialization', 'like', "%{$specialization}%");
    }

    public function scopeForExperienceRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('experience_years', [$min, $max]);
    }

    public function scopeWithBio(Builder $query): Builder
    {
        return $query->whereNotNull('bio')->where('bio', '!=', '');
    }

    public function scopeWithoutBio(Builder $query): Builder
    {
        return $query->whereNull('bio')->orWhere('bio', '');
    }

    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->join('users', 'teacher_profiles.user_id', '=', 'users.id')
                    ->where(function($q) use ($name) {
                        $q->where('users.first_name', 'like', "%{$name}%")
                          ->orWhere('users.last_name', 'like', "%{$name}%");
                    })
                    ->select('teacher_profiles.*');
    }

    public function scopeSearchByEmployeeId(Builder $query, string $search): Builder
    {
        return $query->where('employee_id', 'like', "%{$search}%");
    }

    // Accessors
    public function getFormattedEmployeeIdAttribute(): string
    {
        return strtoupper($this->employee_id);
    }

    public function getFullNameAttribute(): string
    {
        return $this->user ? $this->user->first_name . ' ' . ($this->user->last_name ?? '') : 'Unknown User';
    }

    public function getUserEmailAttribute(): string
    {
        return $this->user ? $this->user->email : '';
    }

    public function getIsExperiencedAttribute(): bool
    {
        return $this->experience_years >= 5;
    }

    public function getIsVeteranAttribute(): bool
    {
        return $this->experience_years >= 10;
    }

    public function getExperienceCategoryAttribute(): string
    {
        $years = $this->experience_years;
        
        if ($years < 2) return 'New';
        if ($years < 5) return 'Experienced';
        if ($years < 10) return 'Senior';
        return 'Veteran';
    }

    public function getYearsSinceJoiningAttribute(): float
    {
        if (!$this->joining_date) {
            return 0;
        }
        return $this->joining_date->diffInDays(now()) / 365.25;
    }

    public function getHasSpecializationAttribute(): bool
    {
        return !empty($this->specialization);
    }

    public function getHasQualificationAttribute(): bool
    {
        return !empty($this->qualification);
    }

    // Mutators
    public function setEmployeeIdAttribute($value): void
    {
        $this->attributes['employee_id'] = strtoupper(trim($value));
    }

    public function setQualificationAttribute($value): void
    {
        $this->attributes['qualification'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setSpecializationAttribute($value): void
    {
        $this->attributes['specialization'] = $value ? trim(ucwords(strtolower($value))) : null;
    }

    public function setBioAttribute($value): void
    {
        $this->attributes['bio'] = $value ? trim($value) : null;
    }

    public function setExperienceYearsAttribute($value): void
    {
        $this->attributes['experience_years'] = max(0, (int)$value);
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'user_email' => $this->user_email,
            'employee_id' => $this->formatted_employee_id,
            'qualification' => $this->qualification,
            'specialization' => $this->specialization,
            'joining_date' => $this->joining_date,
            'experience_years' => $this->experience_years,
            'experience_category' => $this->experience_category,
            'is_experienced' => $this->is_experienced,
            'is_veteran' => $this->is_veteran,
            'years_since_joining' => $this->years_since_joining,
            'has_specialization' => $this->has_specialization,
            'has_qualification' => $this->has_qualification,
            'bio' => $this->bio,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getAcademicProfile(): array
    {
        return [
            'qualification' => $this->qualification,
            'specialization' => $this->specialization,
            'has_specialization' => $this->has_specialization,
            'experience_years' => $this->experience_years,
            'experience_category' => $this->experience_category,
        ];
    }

    public function getProfessionalInfo(): array
    {
        return [
            'employee_id' => $this->formatted_employee_id,
            'joining_date' => $this->joining_date,
            'years_since_joining' => $this->years_since_joining,
            'experience_years' => $this->experience_years,
            'experience_category' => $this->experience_category,
        ];
    }

    public function canTeachAdvancedCourses(): bool
    {
        return $this->is_experienced && $this->has_specialization;
    }

    public function getTeachingLevel(): string
    {
        if ($this->is_veteran) return 'Senior Teacher';
        if ($this->is_experienced) return 'Experienced Teacher';
        return 'Junior Teacher';
    }

    public function getYearsToSeniority(): int
    {
        $requiredYears = 5; // Assuming 5 years for seniority
        $currentYears = $this->experience_years;
        
        return $currentYears >= $requiredYears ? 0 : ($requiredYears - $currentYears);
    }

    public function getProfileSummary(): array
    {
        return [
            'employee_id' => $this->formatted_employee_id,
            'name' => $this->full_name,
            'qualification' => $this->qualification,
            'specialization' => $this->specialization,
            'experience' => $this->experience_years . ' years',
            'level' => $this->getTeachingLevel(),
            'bio' => $this->bio,
        ];
    }
}
