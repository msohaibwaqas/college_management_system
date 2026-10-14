<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class StudentCompulsorySubject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'student_compulsory_subjects';

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
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'class_level' => 'string',
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
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClassLevel(Builder $query, string $level): Builder
    {
        return $query->where('class_level', $level);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForClassAndLevel(Builder $query, int $studentId, string $level): Builder
    {
        return $query->where('student_id', $studentId)
                    ->where('class_level', $level);
    }

    public function scopeWithSubjectDetails(Builder $query): Builder
    {
        return $query->with('subject');
    }

    public function scopeWithStudentDetails(Builder $query): Builder
    {
        return $query->with('student');
    }

    // Accessors
    public function getSubjectNameAttribute(): string
    {
        return $this->subject ? $this->subject->name : 'Unknown Subject';
    }

    public function getSubjectCodeAttribute(): string
    {
        return $this->subject ? $this->subject->code : 'N/A';
    }

    public function getStudentNameAttribute(): string
    {
        return $this->student ? $this->student->full_name : 'Unknown Student';
    }

    public function getIsForBothClassesAttribute(): bool
    {
        return $this->subject && $this->subject->class_level === 'both';
    }

    public function getIsRequiredForClassAttribute(): bool
    {
        if (!$this->subject) return false;
        
        return $this->subject->class_level === 'both' || 
               $this->subject->class_level === $this->class_level;
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student_name,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject_name,
            'subject_code' => $this->subject_code,
            'class_level' => $this->class_level,
            'is_for_both_classes' => $this->is_for_both_classes,
            'is_required_for_class' => $this->is_required_for_class,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getSubjectDetails(): array
    {
        if (!$this->subject) {
            return [];
        }
        
        return [
            'name' => $this->subject->name,
            'code' => $this->subject->code,
            'type' => $this->subject->type,
            'class_level' => $this->subject->class_level,
            'total_11' => $this->subject->total_11,
            'theory_12' => $this->subject->theory_12,
            'practical_12' => $this->subject->practical_12,
        ];
    }

    public function isValidForClass(): bool
    {
        return $this->is_required_for_class;
    }

    public function getAcademicYear(): int
    {
        if (!$this->student) {
            return 0;
        }
        
        $admission = $this->student->admissions()
                                  ->where('current_class', $this->class_level)
                                  ->first();
        
        return $admission ? $admission->admission_year : 0;
    }

    public function getStudentClassInfo(): array
    {
        if (!$this->student) {
            return [];
        }
        
        return [
            'class_level' => $this->class_level,
            'student_name' => $this->student_name,
            'student_id' => $this->student_id,
            'admission_year' => $this->getAcademicYear(),
        ];
    }
}
