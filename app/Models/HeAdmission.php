<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
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

    protected $casts = [
        'admission_year' => 'integer',
        'current_semester' => 'integer',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    /**
     * Get unique admission years for HE admissions, ordered descending.
     */
    public static function distinctAdmissionYears(): \Illuminate\Support\Collection
    {
        return static::select('admission_year')
            ->distinct()
            ->whereNotNull('admission_year')
            ->orderByDesc('admission_year')
            ->pluck('admission_year');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
                     ->whereNotIn('status', ['Struck Off', 'struck_off', 'Graduated', 'Dropped', 'Suspended']);
    }

    public function scopeStruckOff(Builder $query): Builder
    {
        return $query->whereIn('status', ['Struck Off', 'struck_off']);
    }

    public function scopeForBatch(Builder $query, int $year): Builder
    {
        return $query->where('admission_year', $year);
    }

    public function scopeForProgram(Builder $query, int $programId): Builder
    {
        return $query->where('program_id', $programId);
    }

    public function scopeForSemester(Builder $query, int $semester): Builder
    {
        return $query->where('current_semester', $semester);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['Active', 'Admitted', 'active', 'admitted']);
    }

    public function getIsGraduatedAttribute(): bool
    {
        return $this->status === 'Graduated';
    }

    public function getIsStruckOffAttribute(): bool
    {
        return in_array(strtolower($this->status), ['struck off', 'struck_off']);
    }

    public function getRollNumberAttribute(): ?string
    {
        return $this->roll_no;
    }

    public function getAcademicLevelAttribute(): string
    {
        return 'Semester ' . $this->current_semester;
    }

    public function getStatusColor(): string
    {
        return \App\Enums\AdmissionStatus::badgeFor($this->status);
    }

    public function getStatusBadgeAttribute(): string
    {
        return \App\Enums\AdmissionStatus::badgeFor($this->status);
    }

    // Domain State Transitions
    public function strikeOff(?int $userId = null): void
    {
        $this->update([
            'status'     => 'Struck Off',
            'updated_by' => $userId,
        ]);
    }

    public function activate(?int $userId = null): void
    {
        $this->update([
            'status'     => 'Active',
            'updated_by' => $userId,
        ]);
    }

    public function graduate(?int $userId = null): void
    {
        $this->update([
            'status'     => 'Graduated',
            'updated_by' => $userId,
        ]);
    }

    public function drop(?int $userId = null): void
    {
        $this->update([
            'status'     => 'Dropped',
            'updated_by' => $userId,
        ]);
    }

    public function canPromoteToNextSemester(): bool
    {
        return in_array($this->status, ['Active', 'Admitted', 'active', 'admitted']);
    }

    /**
     * Promote student to next semester or graduate if past final semester.
     */
    public function promoteToNextSemester(?int $targetSemester = null, ?int $userId = null): bool
    {
        if (!$this->canPromoteToNextSemester()) {
            return false;
        }

        if ($targetSemester) {
            $this->update([
                'current_semester' => $targetSemester,
                'status'           => 'Active',
                'updated_by'       => $userId,
            ]);
            return true;
        }

        if ($this->current_semester >= 8) {
            $this->graduate($userId);
            return true;
        }

        $this->update([
            'current_semester' => $this->current_semester + 1,
            'status'           => 'Active',
            'updated_by'       => $userId,
        ]);

        return true;
    }

    // Relationships
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
