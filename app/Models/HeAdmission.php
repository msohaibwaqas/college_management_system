<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Semester;
use App\Models\HeStudentSubject;

class HeAdmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'program_id',
        'roll_no',
        'uni_reg_no',
        'uni_roll_no',
        'admission_year',
        'current_semester',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function booted()
    {
        static::created(function ($admission) {
            $admission->enrollInSemesterSubjects();
        });

        static::updated(function ($admission) {
            if ($admission->isDirty('current_semester')) {
                $admission->enrollInSemesterSubjects();
            }
        });
    }

    /**
     * Automatically enroll student in subjects for their current semester
     */
    public function enrollInSemesterSubjects(): void
    {
        $subjects = Semester::where('program_id', $this->program_id)
            ->where('semester_no', $this->current_semester)
            ->get();

        foreach ($subjects as $semesterSubject) {
            HeStudentSubject::firstOrCreate([
                'he_admission_id' => $this->id,
                'bs_subject_id' => $semesterSubject->bs_subject_id,
                'semester_no' => $semesterSubject->semester_no,
            ], [
                'status' => 'Enrolled',
                'created_by' => auth()->id() ?? $this->created_by,
            ]);
        }
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'Active' => 'success',
            'Graduated' => 'primary',
            'Dropped' => 'danger',
            'Suspended' => 'warning',
            'Admitted' => 'info',
            'Applied' => 'secondary',
            default => 'dark'
        };
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(HeStudentSubject::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(HeTeacherAssignment::class);
    }

    public function heResults(): HasMany
    {
        return $this->hasMany(HeResult::class, 'he_admission_id', 'id');
    }
}
