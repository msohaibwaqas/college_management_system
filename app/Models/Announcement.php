<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'body', 'recipient_group', 'is_holiday', 'holiday_date', 'holiday_end_date', 'scheduled_at', 'sent_at', 
        'created_by', 'updated_by', 'deleted_by'
    ];

    protected $casts = [
        'is_holiday' => 'boolean',
        'holiday_date' => 'date',
        'holiday_end_date' => 'date',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    /**
     * Check if this announcement defines a multi-day vacation period.
     */
    public function isVacationRange(): bool
    {
        return (bool) ($this->is_holiday && $this->holiday_date && $this->holiday_end_date && $this->holiday_end_date->gt($this->holiday_date));
    }

    /**
     * Calculate total duration in days of this holiday/vacation announcement.
     */
    public function getHolidayDurationDaysAttribute(): ?int
    {
        if (!$this->is_holiday || !$this->holiday_date) {
            return null;
        }

        if ($this->holiday_end_date && $this->holiday_end_date->gte($this->holiday_date)) {
            return $this->holiday_date->diffInDays($this->holiday_end_date) + 1;
        }

        return 1;
    }

    /**
     * Get a human-readable label for the holiday/vacation date range.
     */
    public function getHolidayRangeLabelAttribute(): ?string
    {
        if (!$this->is_holiday || !$this->holiday_date) {
            return null;
        }

        if ($this->isVacationRange()) {
            $days = $this->holiday_duration_days;
            return "{$this->holiday_date->format('d M Y')} - {$this->holiday_end_date->format('d M Y')} ({$days} days)";
        }

        return "{$this->holiday_date->format('d M Y')} (1 day)";
    }
}

