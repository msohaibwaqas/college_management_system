<?php

namespace App\Services\Voucher;

use App\Contracts\PrintableVoucherInterface;
use App\Models\FeeVoucher;
use App\Models\StudentFine;
use App\Models\InstituteSetting;
use App\Services\Voucher\Adapters\FeeVoucherAdapter;
use App\Services\Voucher\Adapters\FineVoucherAdapter;
use InvalidArgumentException;

/**
 * Class VoucherFactory
 * 
 * Factory responsible for creating standard PrintableVoucherInterface instances
 * from various domain models.
 */
class VoucherFactory
{
    /**
     * Create a printable voucher adapter from a supported model.
     *
     * @param mixed $model
     * @param InstituteSetting|null $settings
     * @return PrintableVoucherInterface
     */
    public static function create(mixed $model, ?InstituteSetting $settings = null): PrintableVoucherInterface
    {
        $settings = $settings ?: InstituteSetting::getSettings();

        if ($model instanceof PrintableVoucherInterface) {
            return $model;
        }

        if ($model instanceof FeeVoucher) {
            return self::fromFeeVoucher($model, $settings);
        }

        if ($model instanceof StudentFine) {
            return self::fromFine($model, $settings);
        }

        throw new InvalidArgumentException(
            "Unsupported model for voucher printing: " . (is_object($model) ? get_class($model) : gettype($model))
        );
    }

    /**
     * Create from FeeVoucher model.
     */
    public static function fromFeeVoucher(FeeVoucher $voucher, ?InstituteSetting $settings = null): FeeVoucherAdapter
    {
        return new FeeVoucherAdapter($voucher, $settings);
    }

    /**
     * Create from StudentFine model.
     */
    public static function fromFine(StudentFine $fine, ?InstituteSetting $settings = null): FineVoucherAdapter
    {
        return new FineVoucherAdapter($fine, $settings);
    }
}
