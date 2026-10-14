<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineTransaction extends Model
{
    protected $fillable = [
        'fee_voucher_id', 'gateway', 'transaction_reference', 
        'amount', 'mobile_number', 'status', 'gateway_response', 'paid_at'
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at' => 'datetime'
    ];

    public function feeVoucher()
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }
}
