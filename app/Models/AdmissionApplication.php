<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class AdmissionApplication extends Model
{
    use SoftDeletes;

    protected $table = 'admission_applications';

    protected $fillable = [
        'application_number', 'source', 'program_type', 'program_id',
        'first_name', 'last_name', 'date_of_birth', 'gender', 'cnic',
        'religion', 'nationality', 'domicile', 'picture',
        'father_name', 'father_cnic', 'father_contact', 'father_occupation', 'father_annual_income',
        'student_contact', 'whatsapp_no', 'email', 'address',
        'ssc_result_card', 'b_form_pic', 'father_cnic_pic',
        'bise_id', 'ssc_roll_no', 'ssc_reg_no', 'ssc_school_name',
        'ssc_total_marks', 'ssc_obtained_marks', 'ssc_passing_year', 'ssc_passing_session', 'ssc_group',
        // HSSC fields (ADP/BS applicants)
        'hssc_bise_id', 'hssc_roll_no', 'hssc_reg_no', 'hssc_college_name',
        'hssc_total_marks', 'hssc_obtained_marks', 'hssc_passing_year', 'hssc_passing_session', 'hssc_group',
        'admission_year', 'elective_subject_1', 'elective_subject_2', 'elective_subject_3',
        'blood_group_id', 'quota_id',
        'status', 'reviewed_by', 'reviewed_at', 'review_remarks',
        'fee_voucher_number', 'paid_voucher_path', 'fee_confirmed_at',
        'subject_group', 'subject_group_id', 'assigned_roll_no',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'reviewed_at' => 'datetime',
        'fee_confirmed_at' => 'datetime',
        'ssc_total_marks' => 'integer',
        'ssc_obtained_marks' => 'integer',
        'ssc_passing_year' => 'integer',
        'hssc_total_marks' => 'integer',
        'hssc_obtained_marks' => 'integer',
        'hssc_passing_year' => 'integer',
        'admission_year' => 'integer',
        'father_annual_income' => 'integer',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function bise(): BelongsTo
    {
        return $this->belongsTo(BiseRecord::class, 'bise_id', 'id');
    }

    public function hsssBise(): BelongsTo
    {
        return $this->belongsTo(BiseRecord::class, 'hssc_bise_id', 'id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id', 'id');
    }

    public function electiveSubject1(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'elective_subject_1', 'id');
    }

    public function electiveSubject2(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'elective_subject_2', 'id');
    }

    public function electiveSubject3(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'elective_subject_3', 'id');
    }

    public function bloodGroup(): BelongsTo
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    public function quota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'quota_id', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function voucher(): HasOne
    {
        return $this->hasOne(FeeVoucher::class, 'application_id', 'id');
    }

    public function subjectGroupRelation(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id', 'id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'fee_paid');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('admission_year', $year);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('source', 'online');
    }

    public function scopeWalkIn(Builder $query): Builder
    {
        return $query->where('source', 'walk_in');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->last_name ?? ''));
    }

    public function getSscPercentageAttribute(): float
    {
        if (!$this->ssc_total_marks) return 0.0;
        return round(($this->ssc_obtained_marks / $this->ssc_total_marks) * 100, 2);
    }

    public function getHsscPercentageAttribute(): float
    {
        if (!$this->hssc_total_marks) return 0.0;
        return round(($this->hssc_obtained_marks / $this->hssc_total_marks) * 100, 2);
    }

    public function isHeProgram(): bool
    {
        return in_array($this->program_type, ['ADP', 'BS', 'DIT']);
    }

    public function getElectiveSubjectIdsAttribute(): array
    {
        return array_filter([
            $this->elective_subject_1,
            $this->elective_subject_2,
            $this->elective_subject_3,
        ]);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'submitted' => 'info',
            'under_review' => 'warning',
            'approved' => 'primary',
            'fee_pending' => 'warning',
            'fee_paid' => 'success',
            'admitted' => 'success',
            'rejected' => 'danger',
            default => 'dark',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Generate unique application number for a given year
     * Format: APP{YY}{00001}
     */
    public static function generateApplicationNumber(int $admissionYear): string
    {
        return \DB::transaction(function () use ($admissionYear) {
            $prefix = 'APP';
            $year = substr($admissionYear, -2);

            // Using lockForUpdate to prevent race conditions during concurrent requests
            $lastApp = self::withTrashed()
                ->where('application_number', 'like', "{$prefix}{$year}%")
                ->orderBy('application_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastApp) {
                $lastNum = intval(substr($lastApp->application_number, 5));
                $count = $lastNum + 1;
            } else {
                $count = 1;
            }

            return sprintf('%s%s%05d', $prefix, $year, $count);
        });
    }

    /**
     * Classify the elective subject combination into a subject group.
     *
     * Pre-Medical:      Physics + Chemistry + Biology
     * Pre-Engineering:  Physics + Chemistry + Math
     * General Science:  Math + any 2 from (Computer, Physics, Economics, Statistics)
     * Humanities:       Anything else
     */
    public function classifySubjectGroup(): string
    {
        // Get subject names (lowercase for comparison)
        $subjects = Subject::whereIn('id', $this->elective_subject_ids)
            ->pluck('name')
            ->map(fn($name) => strtolower(trim($name)))
            ->toArray();
        sort($subjects);

        $hasPhysics = in_array('physics', $subjects);
        $hasChemistry = in_array('chemistry', $subjects);
        $hasBiology = in_array('biology', $subjects);
        $hasMath = in_array('mathematics', $subjects) || in_array('math', $subjects) || in_array('maths', $subjects);
        $hasComputer = in_array('computer science', $subjects) || in_array('computer', $subjects) || in_array('comp. science', $subjects);
        $hasEconomics = in_array('economics', $subjects);
        $hasStatistics = in_array('statistics', $subjects);

        // Pre-Medical: Physics + Chemistry + Biology
        if ($hasPhysics && $hasChemistry && $hasBiology) {
            return 'Pre-Medical';
        }

        // Pre-Engineering: Physics + Chemistry + Math
        if ($hasPhysics && $hasChemistry && $hasMath) {
            return 'Pre-Engineering';
        }

        // General Science: Math + any 2 from (Computer, Physics, Economics, Statistics)
        if ($hasMath) {
            $generalScienceSubjects = array_filter([$hasComputer, $hasPhysics, $hasEconomics, $hasStatistics]);
            if (count($generalScienceSubjects) >= 2) {
                return 'General Science';
            }
        }

        // Default: Humanities
        return 'Humanities';
    }

    /**
     * Auto-classify and save subject group
     */
    public function updateSubjectGroup(): void
    {
        if ($this->subject_group_id) {
            $group = SubjectGroup::find($this->subject_group_id);
            if ($group) {
                $this->subject_group = $group->name;
                $this->save();
                return;
            }
        }

        $this->subject_group = $this->classifySubjectGroup();
        $this->save();
    }

    /**
     * Check if admissions are currently open
     */
    public static function areAdmissionsOpen(string $program = 'Inter'): bool
    {
        return AdmissionSetting::isOpen($program);
    }

    /**
     * Check if any admission program is open
     */
    public static function isAnyOpen(): bool
    {
        return self::areAdmissionsOpen('Inter') || 
               self::areAdmissionsOpen('ADP') || 
               self::areAdmissionsOpen('BS');
    }
}
