<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class InvigilatorAssignment extends Model
{
    protected $fillable = [
        'seating_plan_id',
        'he_seating_plan_id',
        'room_id',
        'staff_id',
        'exam_date',
        'is_auto_assigned',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'is_auto_assigned' => 'boolean',
    ];

    /**
     * Get the seating plan
     */
    public function seatingPlan(): BelongsTo
    {
        return $this->belongsTo(SeatingPlan::class);
    }

    /**
     * Get the HE seating plan
     */
    public function heSeatingPlan(): BelongsTo
    {
        return $this->belongsTo(HeSeatingPlan::class);
    }

    /**
     * Get the room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the staff member (invigilator)
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Scope to filter by date
     */
    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('exam_date', $date);
    }

    /**
     * Scope to filter by room
     */
    public function scopeForRoom(Builder $query, int $roomId): Builder
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope to filter auto-assigned
     */
    public function scopeAutoAssigned(Builder $query): Builder
    {
        return $query->where('is_auto_assigned', true);
    }

    /**
     * Scope to filter manually assigned
     */
    public function scopeManualAssigned(Builder $query): Builder
    {
        return $query->where('is_auto_assigned', false);
    }

    /**
     * Get duty count for a staff member in a date range
     */
    public static function getDutyCount(int $staffId, $startDate, $endDate): int
    {
        return self::where('staff_id', $staffId)
            ->whereBetween('exam_date', [$startDate, $endDate])
            ->distinct('exam_date')
            ->count('exam_date');
    }
}
