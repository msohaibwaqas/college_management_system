<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StudentFine extends Model
{
    protected $table = 'student_fines';

    protected $fillable = [
        'student_id',
        'admission_id',
        'fine_rule_id',
        'fine_type',
        'exam_name',
        'subject_id',
        'academic_year',
        'period',
        'units_count',
        'amount_per_unit',
        'total_amount',
        'paid_amount',
        'waived_amount',
        'balance_amount',
        'status',
        'reason',
        'paid_at',
        'paid_by',
        'payment_reference',
        'payment_note',
        'waived_at',
        'waived_by',
        'waiver_reason',
        'generated_by',
    ];

    protected $casts = [
        'amount_per_unit' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'waived_amount'   => 'decimal:2',
        'balance_amount'  => 'decimal:2',
        'units_count'     => 'integer',
        'academic_year'   => 'integer',
        'paid_at'         => 'datetime',
        'waived_at'       => 'datetime',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admissions::class, 'admission_id');
    }

    public function fineRule(): BelongsTo
    {
        return $this->belongsTo(FineRule::class, 'fine_rule_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function payerCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function waiverAuthorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partially_paid']);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function scopeWaived(Builder $query): Builder
    {
        return $query->where('status', 'waived');
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
            ->orWhere('payment_reference', 'like', "%{$search}%")
            ->orWhere('reason', 'like', "%{$search}%")
            ->orWhere('exam_name', 'like', "%{$search}%");
        });
    }

    // Actions & Helpers
    public function markPayment(float $amount, ?string $reference = null, ?string $note = null, ?int $userId = null): void
    {
        $newPaid = round((float) $this->paid_amount + $amount, 2);
        $balance = max(0, round((float) $this->total_amount - $newPaid - (float) $this->waived_amount, 2));
        
        $status = $balance <= 0 ? 'paid' : 'partially_paid';

        $this->update([
            'paid_amount'       => $newPaid,
            'balance_amount'    => $balance,
            'status'            => $status,
            'paid_at'           => now(),
            'paid_by'           => $userId,
            'payment_reference' => $reference ?? $this->payment_reference,
            'payment_note'      => $note ?? $this->payment_note,
        ]);
    }

    public function waiveFine(float $amount, string $reason, ?int $userId = null): void
    {
        $newWaived = min((float) $this->total_amount, round((float) $this->waived_amount + $amount, 2));
        $balance = max(0, round((float) $this->total_amount - (float) $this->paid_amount - $newWaived, 2));

        $status = $balance <= 0 && (float) $this->paid_amount == 0 ? 'waived' : ($balance <= 0 ? 'paid' : 'partially_paid');

        $this->update([
            'waived_amount' => $newWaived,
            'balance_amount'=> $balance,
            'status'        => $status,
            'waived_at'     => now(),
            'waived_by'     => $userId,
            'waiver_reason' => $reason,
        ]);
    }

    public function getFormattedTypeAttribute(): string
    {
        return match ($this->fine_type) {
            'lecture_absent' => 'Lecture Absence',
            'test_absent'    => 'Test Absence',
            'test_fail'      => 'Test Failure',
            default          => ucfirst(str_replace('_', ' ', $this->fine_type)),
        };
    }

    public function getBalanceAmountAttribute($value): float
    {
        if ($value !== null) {
            return (float) $value;
        }
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount - (float) $this->waived_amount, 2));
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            "paid"           => "success",
            "partially_paid" => "warning",
            "waived"         => "secondary",
            default          => "danger",
        };
    }
}
