<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class SscRecord extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'ssc_records';

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
        'bise_id',
        'roll_no',
        'reg_no',
        'school_name',
        'total_mark',
        'obtained_mark',
        'passing_year',
        'passing_session',
        'ssc_group',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'passing_year' => 'integer',
        'total_mark' => 'integer',
        'obtained_mark' => 'integer',
        'passing_session' => 'string',
        'ssc_group' => 'string',
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

    public function bise(): BelongsTo
    {
        return $this->belongsTo(BiseRecord::class, 'bise_id', 'id');
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
    public function scopeForGroup(Builder $query, string $group): Builder
    {
        return $query->where('ssc_group', $group);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('passing_year', $year);
    }

    public function scopeForSession(Builder $query, string $session): Builder
    {
        return $query->where('passing_session', $session);
    }

    public function scopeForBise(Builder $query, int $biseId): Builder
    {
        return $query->where('bise_id', $biseId);
    }

    public function scopeForSchool(Builder $query, string $schoolName): Builder
    {
        return $query->where('school_name', 'like', "%{$schoolName}%");
    }

    public function scopePassed(Builder $query): Builder
    {
        return $query->where('obtained_mark', '>=', 40); // Assuming 40% is passing
    }

    public function scopeSearchByRollNo(Builder $query, string $rollNo): Builder
    {
        return $query->where('roll_no', 'like', "%{$rollNo}%");
    }

    public function scopeSearchByRegNo(Builder $query, string $regNo): Builder
    {
        return $query->where('reg_no', 'like', "%{$regNo}%");
    }

    // Accessors
    public function getPercentageAttribute(): float
    {
        if ($this->total_mark == 0) {
            return 0.0;
        }
        return round(($this->obtained_mark / $this->total_mark) * 100, 2);
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
        return $this->percentage >= 40; // Assuming 40% is passing
    }

    public function getDivisionAttribute(): string
    {
        $percentage = $this->percentage;
        
        if ($percentage >= 75) return 'First Division';
        if ($percentage >= 60) return 'Second Division';
        if ($percentage >= 45) return 'Third Division';
        return 'Fail';
    }

    public function getFormattedRollNoAttribute(): string
    {
        return strtoupper($this->roll_no);
    }

    public function getFormattedRegNoAttribute(): string
    {
        return strtoupper($this->reg_no);
    }

    // Mutators
    public function setRollNoAttribute($value): void
    {
        $this->attributes['roll_no'] = strtoupper(trim($value));
    }

    public function setRegNoAttribute($value): void
    {
        $this->attributes['reg_no'] = strtoupper(trim($value));
    }

    public function setSchoolNameAttribute($value): void
    {
        $this->attributes['school_name'] = ucwords(strtolower(trim($value)));
    }

    public function setTotalMarkAttribute($value): void
    {
        $this->attributes['total_mark'] = (int)$value;
    }

    public function setObtainedMarkAttribute($value): void
    {
        $this->attributes['obtained_mark'] = (int)$value;
    }

    // Custom methods
    public function hasValidMarks(): bool
    {
        return $this->obtained_mark <= $this->total_mark && $this->obtained_mark >= 0;
    }

    public function getMarksSummary(): array
    {
        return [
            'total' => $this->total_mark,
            'obtained' => $this->obtained_mark,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'division' => $this->division,
            'is_passed' => $this->is_passed,
        ];
    }

    public function getFullDetails(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'bise' => $this->bise ? $this->bise->name : null,
            'roll_no' => $this->formatted_roll_no,
            'reg_no' => $this->formatted_reg_no,
            'school_name' => $this->school_name,
            'total_mark' => $this->total_mark,
            'obtained_mark' => $this->obtained_mark,
            'passing_year' => $this->passing_year,
            'passing_session' => $this->passing_session,
            'ssc_group' => $this->ssc_group,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'division' => $this->division,
            'is_passed' => $this->is_passed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function isEligibleForAdmission(): bool
    {
        return $this->is_passed && $this->ssc_group === 'Science'; // Assuming science students are eligible
    }

    public function getCertificateInfo(): array
    {
        return [
            'roll_no' => $this->formatted_roll_no,
            'reg_no' => $this->formatted_reg_no,
            'school' => $this->school_name,
            'bise' => $this->bise ? $this->bise->name : 'Unknown',
            'marks' => $this->getMarksSummary(),
            'year' => $this->passing_year,
            'session' => $this->passing_session,
        ];
    }
}
