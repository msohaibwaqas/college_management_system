<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdmissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_type',
        'is_open',
        'start_date',
        'end_date',
        'admission_year',
    ];

    protected $casts = [
        'is_open' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'admission_year' => 'integer',
    ];

    /**
     * Get settings for a specific program
     */
    public static function getForProgram(string $program)
    {
        return self::where('program_type', $program)->first() ?: new self([
            'program_type' => $program,
            'is_open' => false,
            'admission_year' => date('Y'),
        ]);
    }

    /**
     * Check if admissions are open for a specific program
     */
    public static function isOpen(string $program): bool
    {
        $settings = self::where('program_type', $program)->first();
        if (!$settings || !$settings->is_open || !$settings->start_date || !$settings->end_date) {
            return false;
        }

        $today = now()->startOfDay();
        return $today->between($settings->start_date->startOfDay(), $settings->end_date->endOfDay());
    }
}
