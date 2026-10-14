<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeVoucherItem extends Model
{
    protected $table = 'fee_voucher_items';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_refundable' => 'boolean',
    ];

    public function feeVoucher()
    {
        return $this->belongsTo(FeeVoucher::class);
    }

    public function feeHead()
    {
        return $this->belongsTo(FeeHead::class);
    }
}
