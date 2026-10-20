<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeVoucher extends Model
{
    use SoftDeletes;

    protected $table = 'fee_vouchers';

    protected $fillable = [
        'voucher_number', 'application_id', 'student_id',
        'applicant_name', 'father_name',
        'amount', 'paid_amount',
        'issue_date', 'due_date', 'payment_date',
        'status',
        'bank_name', 'account_number', 'bank_branch',
        'remarks',
        'issued_by', 'confirmed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function items()
    {
        return $this->hasMany(FeeVoucherItem::class, 'fee_voucher_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class, 'application_id', 'id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && now()->isAfter($this->due_date);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getAdmissionYearAttribute(): string
    {
        if ($this->application) {
            return (string)$this->application->admission_year;
        }
        if ($this->student) {
            $admission = $this->student->admissions()->orderBy('admission_year', 'desc')->first();
            return $admission ? (string)$admission->admission_year : now()->year;
        }
        return (string)now()->year;
    }

    public function getSubjectGroupAttribute(): string
    {
        if ($this->application) {
            return (string)$this->application->subject_group;
        }
        if ($this->student) {
            return $this->student->subject_group;
        }
        return 'N/A';
    }

    public function getCnicAttribute(): string
    {
        if ($this->application) {
            return (string)$this->application->cnic;
        }
        if ($this->student) {
            return (string)$this->student->student_cnic;
        }
        return 'N/A';
    }

    public function getCollegeRollNoAttribute(): ?string
    {
        if ($this->student) {
            $admission = $this->student->admissions->whereIn('status', ['Active', 'Admitted'])->first();
            if ($admission && $admission->college_roll_no) {
                return $admission->college_roll_no;
            }
            $heAdmission = $this->student->heAdmissions->whereIn('status', ['Active', 'Admitted'])->first();
            if ($heAdmission && $heAdmission->roll_no) {
                return $heAdmission->roll_no;
            }
        }
        return null;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Generate unique voucher number
     * Format: FV{YY}{000001}
     */
    public static function generateVoucherNumber(): string
    {
        $prefix = 'FV';
        $year = date('y');

        $lastVoucher = self::withTrashed()
            ->where('voucher_number', 'like', "{$prefix}{$year}%")
            ->orderBy('voucher_number', 'desc')
            ->first();

        if ($lastVoucher) {
            $lastNum = intval(substr($lastVoucher->voucher_number, 4));
            $count = $lastNum + 1;
        } else {
            $count = 1;
        }

        return sprintf('%s%s%06d', $prefix, $year, $count);
    }

    /**
     * Generate a voucher for an application using institute settings
     */
    public static function generateForApplication(AdmissionApplication $application, ?float $customAmount = null): self
    {
        $settings = InstituteSetting::getSettings();

        $amount = $customAmount ?? $settings->admission_fee_amount ?? 0;

        return self::create([
            'voucher_number' => self::generateVoucherNumber(),
            'application_id' => $application->id,
            'applicant_name' => $application->full_name,
            'father_name' => $application->father_name,
            'amount' => $amount,
            'paid_amount' => 0,
            'issue_date' => now(),
            'due_date' => now()->addDays(15),
            'status' => 'pending',
            'bank_name' => $settings->bank_name,
            'account_number' => $settings->bank_account_number,
            'bank_branch' => $settings->bank_branch,
            'issued_by' => auth()->id(),
        ]);
    }
    public function onlineTransactions()
    {
        return $this->hasMany(OnlineTransaction::class, 'fee_voucher_id');
    }

    /**
     * Confirm voucher payment.
     */
    public function confirmPayment(float $paidAmount, ?string $paymentDate = null, ?int $confirmedBy = null): void
    {
        $this->update([
            'paid_amount'  => $paidAmount,
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'status'       => 'paid',
            'confirmed_by' => $confirmedBy ?? auth()->id(),
        ]);
    }
}
