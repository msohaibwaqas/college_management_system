<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvigilatorDutyRule extends Model
{
    protected $fillable = [
        'designation_id',
        'max_duty_days',
    ];

    protected $casts = [
        'max_duty_days' => 'integer',
    ];

    /**
     * Get the designation for this duty rule
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get max duty days for a designation
     */
    public static function getMaxDutyDays(int $designationId): ?int
    {
        return self::where('designation_id', $designationId)->value('max_duty_days');
    }
}
