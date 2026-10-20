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

        if ($carbonDate->isSunday()) {
            return [true, "Sunday (Weekly Off Day)"];
        }

        $publicHoliday = PublicHoliday::whereDate('date', $dateString)->where('is_active', true)->first();
        if ($publicHoliday) {
            return [true, "Public Holiday: {$publicHoliday->title}"];
        }

        $holidayAnnouncement = Announcement::where(function ($q) use ($dateString) {
            $q->where(function ($sub) use ($dateString) {
                $sub->where('is_holiday', true)
                    ->whereDate('holiday_date', $dateString);
            })->orWhere(function ($sub) use ($dateString) {
                $sub->where(function ($kw) {
                    $kw->where('title', 'LIKE', '%holiday%')
                       ->orWhere('body', 'LIKE', '%holiday%')
                       ->orWhere('title', 'LIKE', '%vacation%')
                       ->orWhere('title', 'LIKE', '%off day%');
                })
                ->where(function ($dt) use ($dateString) {
                    $dt->whereDate('holiday_date', $dateString)
                       ->orWhereDate('scheduled_at', $dateString)
                       ->orWhereDate('created_at', $dateString);
                });
            });
        })->first();

        if ($holidayAnnouncement) {
            return [true, "Announced Holiday: {$holidayAnnouncement->title}"];
        }

        return [false, null];
    }
}
