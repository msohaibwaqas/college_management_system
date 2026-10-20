<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StruckOffSetting extends Model
{
    protected $table = 'struck_off_settings';

    protected $fillable = [
        'absent_threshold',
        'max_readmissions_per_year',
        'calculation_period',
    ];

    protected $casts = [
        'absent_threshold'          => 'integer',
        'max_readmissions_per_year' => 'integer',
    ];

    /**
     * Get or create current settings with safe fallbacks
     */
    public static function getSettings(): self
    {
        try {
            if (Schema::hasTable('struck_off_settings')) {
                $setting = static::first();
                if ($setting) {
                    return $setting;
                }
                return static::create([
                    'absent_threshold'          => 10,
                    'max_readmissions_per_year' => 2,
                    'calculation_period'        => 'monthly',
                ]);
            }
        } catch (\Exception $e) {
            // Fallback object below
        }

        $fallback = new static([
            'absent_threshold'          => 10,
            'max_readmissions_per_year' => 2,
            'calculation_period'        => 'monthly',
        ]);
        $fallback->id = 1;
        return $fallback;
    }
}
