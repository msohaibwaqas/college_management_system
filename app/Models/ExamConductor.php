<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ExamConductor extends Model
{
    protected $fillable = [
        'staff_id',
        'exam_start_date',
        'exam_end_date',
        'reason',
    ];

    protected $casts = [
        'exam_start_date' => 'date',
        'exam_end_date' => 'date',
    ];

    /**
     * Get the staff member
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scope to get conductors for a date range
     */
    public function scopeForDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('exam_start_date', [$startDate, $endDate])
              ->orWhereBetween('exam_end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('exam_start_date', '<=', $startDate)
                     ->where('exam_end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Get staff IDs who are exam conductors for a date range
     */
    public static function getExemptedStaffIds($startDate, $endDate): array
    {
        return self::forDateRange($startDate, $endDate)->pluck('staff_id')->toArray();
    }
}
