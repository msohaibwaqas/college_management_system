<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class StudentTeacherSubject extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('staff_id')) {
                // When a student is reassigned to a different teacher,
                // their attendance section from the old teacher is no longer valid.
                $model->attendance_section = null;
            }
        });
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'student_teacher_subjects';

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
        'staff_id',
        'subject_id',
        'class_level',
        'academic_year',
        'assignment_type',
        'attendance_section',
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
        'assignment_type' => 'string',
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
    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

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

    public function scopeForAssignmentType(Builder $query, string $type): Builder
    {
        return $query->where('assignment_type', $type);
    }

    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('assignment_type', 'compulsory');
    }

    public function scopeElective(Builder $query): Builder
    {
        return $query->where('assignment_type', 'elective');
    }

    public function scopeWithTeacherDetails(Builder $query): Builder
    {
        return $query->with('staff');
    }

    public function scopeWithStudentDetails(Builder $query): Builder
    {
        return $query->with('student');
    }

    public function scopeWithSubjectDetails(Builder $query): Builder
    {
        return $query->with('subject');
    }

    public function scopeForAttendanceSection(Builder $query, string $section): Builder
    {
        return $query->where('attendance_section', $section);
    }

    public function scopeUnassignedSection(Builder $query): Builder
    {
        return $query->whereNull('attendance_section');
    }

    // Accessors
    public function getStudentNameAttribute(): string
    {
        return $this->student ? $this->student->full_name : 'Unknown Student';
    }

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
        return $this->class_level === '11';
    }

    public function getIsForClass12Attribute(): bool
    {
        return $this->class_level === '12';
    }

    public function getIsCompulsoryAttribute(): bool
    {
        return $this->assignment_type === 'compulsory';
    }

    public function getIsElectiveAttribute(): bool
    {
        return $this->assignment_type === 'elective';
    }

    public function getTeacherDesignationAttribute(): string
    {
        return $this->staff ? $this->staff->designation : 'Unknown';
    }

    public function getSubjectTypeAttribute(): string
    {
        return $this->subject ? $this->subject->type : 'Unknown';
    }

    // Custom methods
    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->student_name,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->staff_name,
            'teacher_designation' => $this->teacher_designation,
            'subject_id' => $this->subject_id,
            'subject_name' => $this->subject_name,
            'subject_code' => $this->subject_code,
            'subject_type' => $this->subject_type,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'assignment_type' => $this->assignment_type,
            'is_compulsory' => $this->is_compulsory,
            'is_elective' => $this->is_elective,
            'is_for_class_11' => $this->is_for_class_11,
            'is_for_class_12' => $this->is_for_class_12,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function getAssignmentSummary(): array
    {
        return [
            'student' => $this->student_name,
            'teacher' => $this->staff_name,
            'subject' => $this->subject_name,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'assignment_type' => $this->assignment_type,
        ];
    }

    public function getTeacherSubjectInfo(): array
    {
        return [
            'teacher_name' => $this->staff_name,
            'teacher_designation' => $this->teacher_designation,
            'subject_name' => $this->subject_name,
            'subject_code' => $this->subject_code,
            'class_level' => $this->class_level,
        ];
    }

    public function getStudentSubjectInfo(): array
    {
        return [
            'student_name' => $this->student_name,
            'subject_name' => $this->subject_name,
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'assignment_type' => $this->assignment_type,
        ];
    }

    public function isValidAssignment(): bool
    {
        return $this->student && $this->staff && $this->subject;
    }

    public function getAcademicInfo(): array
    {
        return [
            'class_level' => $this->class_level,
            'academic_year' => $this->academic_year,
            'assignment_type' => $this->assignment_type,
            'subject_name' => $this->subject_name,
            'teacher_name' => $this->staff_name,
        ];
    }

    public function getRelatedResults(): array
    {
        return $this->student->results()
                           ->where('subject_id', $this->subject_id)
                           ->where('class_level', $this->class_level)
                           ->where('academic_year', $this->academic_year)
                           ->get()
                           ->toArray();
    }
}
