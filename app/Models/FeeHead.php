<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    protected $table = 'fee_heads';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_refundable' => 'boolean',
        'refund_percentage' => 'decimal:2',
        'belongs_to_practical' => 'boolean',
        'is_computer_fund' => 'boolean',
        'applicable_group' => 'string',
    ];

    public function scopeForProgram($query, string $program)
    {
        return $query->whereIn('program_type', ['All', $program]);
    }

    public function scopeForGender($query, string $gender)
    {
        return $query->whereIn('applicable_gender', ['All', $gender]);
    }

    public function scopeForGroup($query, string $group)
    {
        return $query->where(function ($q) use ($group) {
            $q->where('applicable_group', 'All')
                ->orWhereNull('applicable_group')
                ->orWhere('applicable_group', $group);
        });
    }

    public function scopeOneTime($query)
    {
        return $query->where('payment_frequency', 'one_time');
    }

    public function scopeYearly($query)
    {
        return $query->where('payment_frequency', 'yearly');
    }

    public function scopeSemester($query)
    {
        return $query->where('payment_frequency', 'semester');
    }

    public function scopeMonthly($query)
    {
        return $query->where('payment_frequency', 'monthly');
    }
    
    public function scopeRefundable($query)
    {
        return $query->where('is_refundable', true);
    }

    public function scopePracticalBased($query)
    {
        return $query->where('belongs_to_practical', true);
    }

    public function scopeComputerFund($query)
    {
        return $query->where('is_computer_fund', true);
    }

    public function scopeGeneral($query)
    {
        return $query->where('belongs_to_practical', false)->where('is_computer_fund', false);
    }
}
