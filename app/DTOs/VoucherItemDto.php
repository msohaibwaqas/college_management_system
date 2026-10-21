<?php

namespace App\DTOs;

/**
 * Class VoucherItemDto
 * 
 * Represents a discrete payable head or adjustment line item on a printable voucher.
 */
class VoucherItemDto
{
    public function __construct(
        public readonly string $name,
        public readonly float $amount,
        public readonly ?string $detail = null,
        public readonly bool $isRefundable = false
    ) {}

    public function isDeduction(): bool
    {
        return $this->amount < 0;
    }

    public function getFormattedAmount(): string
    {
        if ($this->amount < 0) {
            return '- ' . number_format(abs($this->amount));
        }

        return number_format($this->amount);
    }
}
