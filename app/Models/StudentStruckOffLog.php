<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StudentStruckOffLog extends Model
{
    protected $table = 'student_struck_off_logs';

    protected $fillable = [
        'student_id',
        'admission_id',
        'struck_off_by',
        'subject_id',
        'absence_count_at_strike',
        'struck_off_at',
        'struck_off_reason',
        'readmitted_by',
        'readmitted_at',
        'readmission_note',
        'academic_year',
        'status',
    ];

    protected $casts = [
        'struck_off_at'  => 'datetime',
        'readmitted_at'  => 'datetime',
        'academic_year'  => 'integer',
        'absence_count_at_strike' => 'integer',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admissions::class, 'admission_id');
    }

    /** The teacher (staff) who struck off the student */
    public function struckOffBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'struck_off_by');
    }

    /** The subject in which the absence threshold was crossed */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /** The admin/clerk/teacher who readmitted the student */
    public function readmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'readmitted_by');
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'struck_off');
    }

    public function scopeReadmitted(Builder $query): Builder
    {
        return $query->where('status', 'readmitted');
    }

    public function scopeForAcademicYear(Builder $query, int $year): Builder
    {
        return $query->where('academic_year', $year);
    }

    public function scopeForStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);
        return $query->where(function ($q) use ($search) {
            $q->whereHas('student', function ($sq) use ($search) {
                $sq->where('first_name', 'like', "%{$search}%")
                   ->orWhere('last_name', 'like', "%{$search}%")
                   ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"])
                   ->orWhereHas('father', function ($fq) use ($search) {
                       $fq->where('name', 'like', "%{$search}%");
                   });
            })
            ->orWhereHas('admission', function ($aq) use ($search) {
                $aq->where('college_roll_no', 'like', "%{$search}%")
                   ->orWhere('bise_roll_11', 'like', "%{$search}%")
                   ->orWhere('bise_roll_12', 'like', "%{$search}%");
            })
            ->orWhereHas('subject', function ($subQ) use ($search) {
                $subQ->where('name', 'like', "%{$search}%");
            })
            ->orWhereHas('struckOffBy', function ($staffQ) use ($search) {
                $staffQ->where('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"]);
            })
            ->orWhere('struck_off_reason', 'like', "%{$search}%");
        });
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    public function isStruckOff(): bool
    {
        return $this->status === 'struck_off';
    }

    public function isReadmitted(): bool
    {
        return $this->status === 'readmitted';
    }

    /**
     * Get the number of times this student has been readmitted in the log's academic year.
     */
    public function getReadmissionsCountForYear(): int
    {
        return static::where('student_id', $this->student_id)
            ->where('academic_year', $this->academic_year)
            ->where('status', 'readmitted')
            ->count();
    }

    /**
     * Get the sequence number of this strike-off in the academic year (1 for 1st strike, 2 for 2nd, etc.).
     */
    public function getStrikeSequenceNumber(): int
    {
        if ($this->exists && $this->id) {
            $priorLogs = static::where('student_id', $this->student_id)
                ->where('academic_year', $this->academic_year)
                ->where('id', '<', $this->id)
                ->count();
            return $priorLogs + 1;
        }

        return $this->getReadmissionsCountForYear() + 1;
    }

    /**
     * Check if this log represents the student's 1st strike-off in the academic year.
     */
    public function isFirstStrikeOff(): bool
    {
        return $this->getStrikeSequenceNumber() === 1;
    }

    /**
     * Check if this log entry can be readmitted according to max allowed limit.
     */
    public function canBeReadmitted(int $maxAllowed): bool
    {
        return $this->isStruckOff() && ($this->getReadmissionsCountForYear() < $maxAllowed);
    }

    /**
     * Check whether a specific user is authorized to readmit this student.
     *
     * Policy Rules:
     * 1. 1st strike-off: The teacher who struck off the student OR any Admin / Clerk can readmit.
     * 2. 2nd & subsequent strike-offs: ONLY Admin and Clerk can readmit (teachers cannot readmit).
     * 3. Must be currently struck off and not exceed the annual readmission limit.
     */
    public function canUserReadmit(?User $user, ?int $maxAllowed = null): bool
    {
        if (!$user || !$this->isStruckOff()) {
            return false;
        }

        if ($maxAllowed !== null && !$this->canBeReadmitted($maxAllowed)) {
            return false;
        }

        $isAdminOrClerk = $user->isAdministrativeStaff()
            || $user->hasAnyRole(['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'super_admin', 'bs_admin', 'bs_clerk'])
            || (bool) $user->is_super_admin;

        // Admin and Clerk can always readmit (1st, 2nd, or subsequent strikes)
        if ($isAdminOrClerk) {
            return true;
        }

        // For 2nd and subsequent strike-offs, ONLY Admin and Clerk can readmit
        if (!$this->isFirstStrikeOff()) {
            return false;
        }

        // For 1st strike-off, the teacher who personally struck off the student can readmit
        $userStaffId = $user->staff?->id ?? ($user->staff_id ?? null);
        if ($userStaffId && (int) $this->struck_off_by === (int) $userStaffId) {
            return true;
        }

        return false;
    }

    /**
     * Get a human-readable reason why a user is not authorized to readmit, or null if authorized.
     */
    public function getReadmissionRestrictionReason(?User $user, ?int $maxAllowed = null): ?string
    {
        if (!$this->isStruckOff()) {
            return 'This student is not currently struck off.';
        }

        if ($maxAllowed !== null && !$this->canBeReadmitted($maxAllowed)) {
            $count = $this->getReadmissionsCountForYear();
            return "This student has already been readmitted {$count} times this academic year (maximum allowed: {$maxAllowed}).";
        }

        if (!$user) {
            return 'You must be logged in to readmit a student.';
        }

        $isAdminOrClerk = $user->isAdministrativeStaff()
            || $user->hasAnyRole(['admin', 'principal', 'clerk', 'hssc_clerk', 'hssc_admin', 'super_admin', 'bs_admin', 'bs_clerk'])
            || (bool) $user->is_super_admin;

        if ($isAdminOrClerk) {
            return null;
        }

        $strikeNo = $this->getStrikeSequenceNumber();
        if (!$this->isFirstStrikeOff()) {
            return "This is strike-off #{$strikeNo}. According to college policy, 2nd and subsequent strike-offs can only be readmitted by an Administrator or Clerk.";
        }

        $userStaffId = $user->staff?->id ?? ($user->staff_id ?? null);
        $teacherName = $this->struckOffBy?->full_name ?? 'the striking teacher';
        if (!$userStaffId || (int) $this->struck_off_by !== (int) $userStaffId) {
            return "Only {$teacherName} (who struck off this student) or an Administrator / Clerk can readmit this student.";
        }

        return null;
    }

    /**
     * Mark this struck-off log entry as readmitted.
     */
    public function markReadmitted(int $userId, ?string $note = null): void
    {
        $this->update([
            'status'           => 'readmitted',
            'readmitted_by'    => $userId,
            'readmitted_at'    => now(),
            'readmission_note' => $note,
        ]);
    }
}
