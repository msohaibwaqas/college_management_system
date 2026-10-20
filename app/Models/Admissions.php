<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use App\Helpers\ModelResolver;


class Admissions extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'admissions';

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
        'admission_year',
        'academic_year',
        'college_roll_no',
        'hssc_reg_no',
        'bise_roll_11',
        'bise_roll_12',
        'current_class',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'admission_year' => 'integer',
        'academic_year' => 'integer',
        'current_class' => 'string',
        'status' => 'string',
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['Active', 'Admitted', 'active', 'admitted'])
                     ->whereNotIn('status', ['Struck Off', 'struck_off', 'Graduated', 'Dropped']);
    }

    public function scopeStruckOff(Builder $query): Builder
    {
        return $query->whereIn('status', ['Struck Off', 'struck_off']);
    }

    public function scopeNotGraduated(Builder $query): Builder
    {
        return $query->where('status', '!=', 'Graduated');
    }

    public function scopeForClass(Builder $query, string $class): Builder
    {
        return $query->where('current_class', $class);
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForAdmissionYear(Builder $query, int $year): Builder
    {
        return $query->where('admission_year', $year);
    }

    public function scopeForBatch(Builder $query, int $year): Builder
    {
        return $query->where('admission_year', $year);
    }

    public function scopeInStatus(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('status', $statuses);
    }

    /**
     * Get unique admission years present in the database, ordered descending.
     */
    public static function distinctAdmissionYears(): \Illuminate\Support\Collection
    {
        return static::select('admission_year')
            ->distinct()
            ->whereNotNull('admission_year')
            ->orderByDesc('admission_year')
            ->pluck('admission_year');
    }

    // Accessors
    public function getIsCurrentClassAttribute(): bool
    {
        return $this->current_class === '11' || $this->current_class === '12';
    }

    public function getIsGraduatedAttribute(): bool
    {
        return $this->status === 'Graduated';
    }

    public function getIsAdmittedAttribute(): bool
    {
        return in_array($this->status, ['Admitted', 'Active', 'Graduated']);
    }

    public function getIsStruckOffAttribute(): bool
    {
        return in_array(strtolower($this->status), ['struck off', 'struck_off']);
    }

    // Mutators
    public function setCollegeRollNoAttribute($value): void
    {
        $this->attributes['college_roll_no'] = strtoupper(trim($value));
    }

    public function setHsscRegNoAttribute($value): void
    {
        $this->attributes['hssc_reg_no'] = strtoupper(trim($value));
    }

    public function setBiseRoll11Attribute($value): void
    {
        $this->attributes['bise_roll_11'] = $value ? strtoupper(trim($value)) : null;
    }

    public function setBiseRoll12Attribute($value): void
    {
        $this->attributes['bise_roll_12'] = $value ? strtoupper(trim($value)) : null;
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

    // Custom methods
    public function isEligibleForFinalExams(): bool
    {
        return $this->status === 'Active' && 
               $this->current_class === '12' && 
               !empty($this->bise_roll_12);
    }

    public function canPromoteToNextClass(): bool
    {
        return $this->current_class === '11' && in_array($this->status, ['Active', 'Admitted', 'active', 'admitted']);
    }

    /**
     * Promote student from Class 11 to Class 12, advancing academic year and subject levels.
     */
    public function promoteToClass12(): bool
    {
        if (!$this->canPromoteToNextClass()) {
            return false;
        }

        $nextAcademicYear = ($this->academic_year ?: $this->admission_year) + 1;

        $this->update([
            'current_class' => '12',
            'academic_year' => $nextAcademicYear,
        ]);

        // Promote compulsory subject levels
        \Illuminate\Support\Facades\DB::table('student_compulsory_subjects')
            ->where('student_id', $this->student_id)
            ->where('class_level', '11')
            ->update(['class_level' => '12']);

        return true;
    }

    public function promoteToNextClass(): bool
    {
        return $this->promoteToClass12();
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
}
