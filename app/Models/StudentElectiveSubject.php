<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;



class StudentElectiveSubject extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'student_elective_subjects';

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
        'subject_group_id',
        'sequence',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'sequence' => 'integer',
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

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id', 'id');
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

    public function scopeForSequence(Builder $query, int $sequence): Builder
    {
        return $query->where('sequence', $sequence);
    }

    public function scopeForSubject(Builder $query, int $subjectId): Builder
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeFirstElective(Builder $query): Builder
    {
        return $query->where('sequence', 1);
    }

    public function scopeSecondElective(Builder $query): Builder
    {
        return $query->where('sequence', 2);
    }

    public function scopeThirdElective(Builder $query): Builder
    {
        return $query->where('sequence', 3);
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

    public function getFormattedSequenceAttribute(): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd'];
        $sequence = $this->sequence;
        $suffix = $suffixes[min($sequence, 3)];
        
        if ($sequence == 0 || $sequence > 3) {
            $suffix = 'th';
        }
        
        return $sequence . $suffix;
    }

    public function getSequenceNameAttribute(): string
    {
        $names = [
            1 => 'First',
            2 => 'Second',
            3 => 'Third',
        ];
        
        return $names[$this->sequence] ?? 'Unknown';
    }

    public function getIsFirstElectiveAttribute(): bool
    {
        return $this->sequence === 1;
    }

    public function getIsSecondElectiveAttribute(): bool
    {
        return $this->sequence === 2;
    }

    public function getIsThirdElectiveAttribute(): bool
    {
        return $this->sequence === 3;
    }

    public function getSubjectDetailsAttribute(): array
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

    // Mutators
    public function setSequenceAttribute($value): void
    {
        $this->attributes['sequence'] = max(1, min(3, (int)$value));
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
            'sequence' => $this->sequence,
            'formatted_sequence' => $this->formatted_sequence,
            'sequence_name' => $this->sequence_name,
            'is_first_elective' => $this->is_first_elective,
            'is_second_elective' => $this->is_second_elective,
            'is_third_elective' => $this->is_third_elective,
            'subject_details' => $this->subject_details,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isValidSequence(): bool
    {
        return in_array($this->sequence, [1, 2, 3]);
    }

    public function getElectiveGroup(): array
    {
        $electives = $this->student->electives()
                                  ->orderBy('sequence')
                                  ->with('subject')
                                  ->get();
        
        $group = [];
        foreach ($electives as $elective) {
            $group[] = [
                'sequence' => $elective->sequence,
                'subject_name' => $elective->subject_name,
                'subject_code' => $elective->subject_code,
            ];
        }
        
        return $group;
    }

    public function getElectiveSummary(): array
    {
        return [
            'sequence' => $this->sequence,
            'subject_name' => $this->subject_name,
            'subject_code' => $this->subject_code,
            'marks_total' => $this->subject ? $this->subject->total_11 : 0,
        ];
    }

    public function getStudentElectiveInfo(): array
    {
        return [
            'student_name' => $this->student_name,
            'sequence' => $this->sequence,
            'subject_name' => $this->subject_name,
            'subject_code' => $this->subject_code,
            'formatted_sequence' => $this->formatted_sequence,
        ];
    }

    public function isEligibleForFinalExam(): bool
    {
        // Check if student has all 3 electives assigned
        $totalElectives = $this->student->electives()->count();
        return $totalElectives === 3;
    }
}
