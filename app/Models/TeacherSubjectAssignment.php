<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class TeacherSubjectAssignment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'teacher_subject_assignments';

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
        'class_level',
        'academic_year',
        'section',
        'max_students',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'class_level' => 'string',
        'academic_year' => 'integer',
        'section' => 'string',
        'max_students' => 'integer',
        'is_active' => 'boolean',
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

    public function scopeForClassLevel(Builder $query, string $level): Builder
    {
        return $query->where('class_level', $level);
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForSection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
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

    public function scopeWithMaxStudents(Builder $query, int $max): Builder
    {
        return $query->where('max_students', '<=', $max);
    }

    public function scopeWithMinStudents(Builder $query, int $min): Builder
    {
        return $query->where('max_students', '>=', $min);
    }

    // Accessors
    public function getStaffNameAttribute(): string
    {
        return $this->staff ? $this->staff->full_name : 'Unknown Staff';
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->subject ? $this->subject->name : 'Unknown Subject';
    }

    public function getSubjectCodeAttribute(): string
    {
        return $this->subject ? $this->subject->code : 'N/A';
    }

    public function getIsForClass11Attribute(): bool
    {
        return $this->class_level === '11' || $this->class_level === 'both';
    }

    public function getIsForClass12Attribute(): bool
    {
        return $this->class_level === '12' || $this->class_level === 'both';
    }

    public function getIsForBothClassesAttribute(): bool
    {
        return $this->class_level === 'both';
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->is_active;
    }

    public function getFormattedSectionAttribute(): string
    {
        return $this->section ?: 'No Section';
    }

    public function getAssignmentStatusAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStudentCapacityAttribute(): string
    {
        if ($this->max_students) {
            return $this->max_students . ' students';
        }
        return 'Unlimited';
    }

    public function getIsOverCapacityAttribute(): bool
    {
        if (!$this->max_students) {
            return false;
        }
        
        $currentStudents = $this->studentTeacherSubjects()
                              ->where('academic_year', $this->academic_year)
                              ->count();
        
        return $currentStudents > $this->max_students;
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
            'subject_code' => $this->subject_code,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'section' => $this->formatted_section,
            'max_students' => $this->max_students,
            'student_capacity' => $this->student_capacity,
            'is_active' => $this->is_active,
            'assignment_status' => $this->assignment_status,
            'is_for_class_11' => $this->is_for_class_11,
            'is_for_class_12' => $this->is_for_class_12,
            'is_for_both_classes' => $this->is_for_both_classes,
            'is_over_capacity' => $this->is_over_capacity,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getAssignmentSummary(): array
    {
        return [
            'teacher' => $this->staff_name,
            'subject' => $this->subject_name,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'section' => $this->formatted_section,
            'max_students' => $this->max_students,
            'is_active' => $this->is_active,
        ];
    }

    public function getTeachingLoad(): array
    {
        return [
            'subject' => $this->subject_name,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'section' => $this->formatted_section,
            'capacity' => $this->student_capacity,
        ];
    }

    public function isValidAssignment(): bool
    {
        return $this->staff && $this->subject && $this->is_active;
    }

    public function getSubjectClassInfo(): array
    {
        return [
            'subject_name' => $this->subject_name,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'section' => $this->formatted_section,
            'teacher' => $this->staff_name,
        ];
    }

    public function getStudentCount(): int
    {
        return $this->studentTeacherSubjects()
                   ->where('academic_year', $this->academic_year)
                   ->count();
    }

    public function getAvailableCapacity(): int
    {
        if (!$this->max_students) {
            return PHP_INT_MAX; // Unlimited capacity
        }
        
        $currentCount = $this->getStudentCount();
        return max(0, $this->max_students - $currentCount);
    }

    public function isAcceptingStudents(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        if (!$this->max_students) {
            return true; // Unlimited capacity
        }
        
        return $this->getStudentCount() < $this->max_students;
    }

    public function getCapacityStatus(): string
    {
        if (!$this->max_students) {
            return 'Unlimited';
        }
        
        $current = $this->getStudentCount();
        $max = $this->max_students;
        
        if ($current >= $max) {
            return 'Full';
        } elseif ($current >= $max * 0.8) {
            return 'Near Capacity';
        } else {
            return 'Available';
        }
    }
}
