<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class Result extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'results';

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
        'student_id',
        'subject_id',
        'class_level',
        'exam_type',
        'exam_name',
        'academic_year',
        'obtained_theory',
        'obtained_practical',
        'total_marks',
        'is_absent',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'class_level' => 'string',
        'exam_type' => 'string',
        'academic_year' => 'integer',
        'obtained_theory' => 'integer',
        'obtained_practical' => 'integer',
        'total_marks' => 'integer',
        'is_absent' => 'boolean',
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
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
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
    public function scopeForClassLevel(Builder $query, string $level): Builder
    {
        return $query->where('class_level', $level);
    }

    public function scopeForExamType(Builder $query, string $type): Builder
    {
        return $query->where('exam_type', $type);
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeWithMarks(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNotNull('obtained_theory')
              ->orWhereNotNull('obtained_practical');
        });
    }

    public function scopeSearchByStudentName(Builder $query, string $name): Builder
    {
        return $query->join('students', 'results.student_id', '=', 'students.id')
                    ->where(function($q) use ($name) {
                        $q->where('students.first_name', 'like', "%{$name}%")
                          ->orWhere('students.last_name', 'like', "%{$name}%");
                    })
                    ->select('results.*');
    }

    // Accessors
    public function getTotalObtainedMarksAttribute(): int
    {
        return ($this->obtained_theory ?? 0) + ($this->obtained_practical ?? 0);
    }

    public function getHasPracticalAttribute(): bool
    {
        return $this->obtained_practical !== null && $this->obtained_practical > 0;
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->obtained_theory !== null;
    }

    public function getGradeAttribute(): string
    {
        $total = $this->total_obtained_marks;
        $totalPossible = $this->total_marks;
        
        if (!$totalPossible) {
            $subject = $this->subject;
            
            if (!$subject || $total === 0) {
                return 'N/A';
            }
            
            $totalPossible = $subject->total_11; // For class 11, or theory_12 + practical_12 for class 12
            if ($subject->class_level === '12') {
                $totalPossible = $subject->theory_12 + $subject->practical_12;
            }
        }
        
        if ($totalPossible === 0) {
            return 'N/A';
        }
        
        $percentage = ($total / $totalPossible) * 100;
        
        if ($percentage >= 80) return 'A+';
        if ($percentage >= 70) return 'A';
        if ($percentage >= 60) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 40) return 'D';
        return 'F';
    }

    public function getPercentageAttribute(): float
    {
        $total = $this->total_obtained_marks;
        $totalPossible = $this->total_marks;
        
        if (!$totalPossible) {
            $subject = $this->subject;
            
            if (!$subject || $total === 0) {
                return 0.0;
            }
            
            $totalPossible = $subject->total_11;
            if ($subject->class_level === '12') {
                $totalPossible = $subject->theory_12 + $subject->practical_12;
            }
        }
        
        if ($totalPossible === 0) {
            return 0.0;
        }
        
        return round(($total / $totalPossible) * 100, 2);
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->grade !== 'F' && $this->grade !== 'N/A';
    }

    // Mutators
    public function setObtainedTheoryAttribute($value): void
    {
        $this->attributes['obtained_theory'] = $value !== '' ? (int)$value : null;
    }

    public function setObtainedPracticalAttribute($value): void
    {
        $this->attributes['obtained_practical'] = $value !== '' ? (int)$value : null;
    }

    public function setAcademicYearAttribute($value): void
    {
        $this->attributes['academic_year'] = (int)$value;
    }

    // Custom methods
    public function hasValidMarks(): bool
    {
        return $this->isComplete || $this->has_practical;
    }

    public function getMarksSummary(): array
    {
        return [
            'theory' => $this->obtained_theory,
            'practical' => $this->obtained_practical,
            'total_obtained' => $this->total_obtained_marks,
            'grade' => $this->grade,
            'percentage' => $this->percentage,
            'is_passed' => $this->is_passed,
        ];
    }

    public function getSubjectName(): string
    {
        return $this->subject ? $this->subject->name : 'Unknown Subject';
    }

    public function getStudentName(): string
    {
        return $this->student ? $this->student->first_name . ' ' . ($this->student->last_name ?? '') : 'Unknown Student';
    }

    public function isEligibleForFinalExam(): bool
    {
        return $this->class_level === '11' && 
               $this->exam_type === 'Send_up' && 
               $this->is_passed;
    }
}
