<?php

namespace App\Enums;

enum AdmissionStatus: string
{
    case Active = 'Active';
    case StruckOff = 'Struck Off';
    case Graduated = 'Graduated';
    case Dropped = 'Dropped';
    case Suspended = 'Suspended';

    /**
     * Get all enum string values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get badge color class for UI.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::StruckOff => 'danger',
            self::Graduated => 'primary',
            self::Dropped => 'secondary',
            self::Suspended => 'warning',
        };
    }

    /**
     * Get badge class statically from string.
     */
    public static function badgeFor(?string $status): string
    {
        return match ($status) {
            'Active', 'admitted', 'Admitted', 'active' => 'success',
            'Struck Off', 'struck_off' => 'danger',
            'Graduated', 'graduated' => 'primary',
            'Dropped', 'dropped' => 'secondary',
            'Suspended', 'suspended' => 'warning',
            default => 'dark',
        };
    }
}
