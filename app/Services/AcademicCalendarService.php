<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\PublicHoliday;
use Carbon\Carbon;

class AcademicCalendarService
{
    /**
     * Check if a given date is Sunday or an announced holiday / public holiday.
     * 
     * @param string $dateString
     * @return array{0: bool, 1: ?string} [isOff, reason]
     */
    public function checkIfSundayOrHoliday(string $dateString): array
    {
        $carbonDate = Carbon::parse($dateString);
        $date = $carbonDate->format('Y-m-d');

        if ($carbonDate->isSunday()) {
            return [true, "Sunday (Weekly Off Day)"];
        }

        $publicHoliday = PublicHoliday::whereDate('date', $date)->where('is_active', true)->first();
        if ($publicHoliday) {
            return [true, "Public Holiday: {$publicHoliday->title}"];
        }

        $holidayAnnouncement = Announcement::where(function ($q) use ($date) {
            $q->where(function ($sub) use ($date) {
                $sub->where('is_holiday', true)
                    ->whereDate('holiday_date', $date);
            })->orWhere(function ($sub) use ($date) {
                $sub->where('is_holiday', true)
                    ->whereNull('holiday_date')
                    ->whereDate('scheduled_at', $date);
            })->orWhere(function ($sub) use ($date) {
                $sub->where(function ($kw) {
                    $kw->where('title', 'LIKE', '%holiday%')
                       ->orWhere('title', 'LIKE', '%vacation%')
                       ->orWhere('title', 'LIKE', '%off day%');
                })
                ->where(function ($dt) use ($date) {
                    $dt->whereDate('holiday_date', $date)
                       ->orWhereDate('scheduled_at', $date);
                });
            });
        })->first();

        if ($holidayAnnouncement) {
            return [true, "Announced Holiday: {$holidayAnnouncement->title}"];
        }

        return [false, null];
    }

    /**
     * Helper to simply check if a date is off.
     */
    public function isOffDay(string $dateString): bool
    {
        return $this->checkIfSundayOrHoliday($dateString)[0];
    }
}
