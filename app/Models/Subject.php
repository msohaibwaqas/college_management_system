<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;

// // Get FQCNs
// $allModels = ModelResolver::all();
// print_r($allModels);

// // Instantiate all models
// $instances = ModelResolver::instances();

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'subjects';

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
        'name',
        'code',
        'type',
        'class_level',
        'total_11',
        'theory_12',
        'practical_12',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'name' => 'string',
        'code' => 'string',
        'type' => 'string',
        'class_level' => 'string',
        'total_11' => 'integer',
        'theory_12' => 'integer',
        'practical_12' => 'integer',
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

    public function studentCompulsorySubjects(): HasMany
    {
        return $this->hasMany(StudentCompulsorySubject::class, 'subject_id', 'id');
    }

    public function studentElectives(): HasMany
    {
        return $this->hasMany(StudentElectiveSubject::class, 'subject_id', 'id');
    }

    public function studentTeacherSubjects(): HasMany
    {
        return $this->hasMany(StudentTeacherSubject::class, 'subject_id', 'id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'subject_id', 'id');
    }

    public function staffSubjectSpecializations(): HasMany
    {
        return $this->hasMany(StaffSubjectSpecialization::class, 'subject_id', 'id');
    }

    // Scopes
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForClassLevel(Builder $query, string $level): Builder
    {
        return $query->where('class_level', $level);
    }

    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('type', 'Compulsory');
    }

    public function scopeElective(Builder $query): Builder
    {
        return $query->where('type', 'Elective');
    }

    public function scopeBothClasses(Builder $query): Builder
    {
        return $query->where('class_level', 'both');
    }

    public function scopeForClass11(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('class_level', '11')
              ->orWhere('class_level', 'both');
        });
    }

    public function scopeForClass12(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->where('class_level', '12')
              ->orWhere('class_level', 'both');
        });
    }

    public function scopeWithMarksRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('total_11', [$min, $max]);
    }

    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeSearchByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', 'like', "%{$code}%");
    }

    // Accessors
    public function getFormattedNameAttribute(): string
    {
        return ucwords(strtolower($this->name));
    }

    public function getIsCompulsoryAttribute(): bool
    {
        return $this->type === 'Compulsory';
    }

    public function getIsElectiveAttribute(): bool
    {
        return $this->type === 'Elective';
    }

    public function getIsForBothClassesAttribute(): bool
    {
        return $this->class_level === 'both';
    }

    public function getIsForClass11Attribute(): bool
    {
        return $this->class_level === '11' || $this->class_level === 'both';
    }

    public function getIsForClass12Attribute(): bool
    {
        return $this->class_level === '12' || $this->class_level === 'both';
    }

    public function getHasPracticalAttribute(): bool
    {
        return $this->practical_12 > 0;
    }

    public function getTotal12Attribute(): int
    {
        return $this->theory_12 + $this->practical_12;
    }

    public function getIsPracticalOnlyAttribute(): bool
    {
        return $this->theory_12 === 0 && $this->practical_12 > 0;
    }

    public function getIsTheoryOnlyAttribute(): bool
    {
        return $this->theory_12 > 0 && $this->practical_12 === 0;
    }

    public function getIsTheoryAndPracticalAttribute(): bool
    {
        return $this->theory_12 > 0 && $this->practical_12 > 0;
    }

    public function getPracticalPercentageAttribute(): float
    {
        if ($this->total_12 === 0) {
            return 0.0;
        }
        return round(($this->practical_12 / $this->total_12) * 100, 2);
    }

    // Mutators
    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = trim(ucwords(strtolower($value)));
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    public function setTypeAttribute($value): void
    {
        $this->attributes['type'] = ucfirst(strtolower($value));
    }

    public function setTotal11Attribute($value): void
    {
        $this->attributes['total_11'] = (int)$value;
    }

    public function setTheory12Attribute($value): void
    {
        $this->attributes['theory_12'] = (int)$value;
    }

    public function setPractical12Attribute($value): void
    {
        $this->attributes['practical_12'] = (int)$value;
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'formatted_name' => $this->formatted_name,
            'code' => $this->code,
            'type' => $this->type,
            'class_level' => $this->class_level,
            'is_compulsory' => $this->is_compulsory,
            'is_elective' => $this->is_elective,
            'is_for_both_classes' => $this->is_for_both_classes,
            'is_for_class_11' => $this->is_for_class_11,
            'is_for_class_12' => $this->is_for_class_12,
            'marks' => [
                'total_11' => $this->total_11,
                'theory_12' => $this->theory_12,
                'practical_12' => $this->practical_12,
                'total_12' => $this->total_12,
                'has_practical' => $this->has_practical,
                'is_practical_only' => $this->is_practical_only,
                'is_theory_only' => $this->is_theory_only,
                'is_theory_and_practical' => $this->is_theory_and_practical,
                'practical_percentage' => $this->practical_percentage,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getMarkingScheme(): array
    {
        return [
            'class_11' => [
                'total' => $this->total_11,
                'type' => $this->type,
            ],
            'class_12' => [
                'theory' => $this->theory_12,
                'practical' => $this->practical_12,
                'total' => $this->total_12,
                'has_practical' => $this->has_practical,
            ],
        ];
    }

    public function getStudentCount(): int
    {
        $compulsoryCount = $this->studentCompulsorySubjects()->count();
        $electiveCount = $this->studentElectives()->count();
        
        return $compulsoryCount + $electiveCount;
    }

    public function getStudentCountByClass(string $classLevel): int
    {
        if ($this->type === 'Compulsory') {
            return $this->studentCompulsorySubjects()
                       ->where('class_level', $classLevel)
                       ->count();
        } else {
            // For electives, we need to check student_teacher_subject
            return $this->studentTeacherSubjects()
                       ->where('class_level', $classLevel)
                       ->count();
        }
    }

    public function getTeacherCount(): int
    {
        return $this->staffSubjectSpecializations()->count();
    }

    public function getAverageResult(): float
    {
        $results = $this->results()->avg('percentage');
        return $results ?? 0.0;
    }

    public function getSubjectCategory(): string
    {
        if (strpos(strtolower($this->name), 'islam') !== false) {
            return 'Religious';
        } elseif (strpos(strtolower($this->name), 'math') !== false) {
            return 'Mathematics';
        } elseif (strpos(strtolower($this->name), 'science') !== false || 
                 strpos(strtolower($this->name), 'physics') !== false ||
                 strpos(strtolower($this->name), 'chemistry') !== false ||
                 strpos(strtolower($this->name), 'biology') !== false) {
            return 'Science';
        } elseif (strpos(strtolower($this->name), 'english') !== false) {
            return 'Language';
        } else {
            return 'General';
        }
    }

    public function getSubjectGroup(): string
    {
        $category = $this->getSubjectCategory();
        
        $groups = [
            'Religious' => 'Religious Studies',
            'Mathematics' => 'Mathematics',
            'Science' => 'Science',
            'Language' => 'Languages',
            'General' => 'General Studies',
        ];
        
        return $groups[$category] ?? 'Other';
    }

    public function isEligibleForPractical(): bool
    {
        return $this->has_practical && $this->class_level !== '11';
    }

    public function getRequiredMarksForPassing(): int
    {
        return (int)($this->total_11 * 0.4); // Assuming 40% is passing
    }
}
