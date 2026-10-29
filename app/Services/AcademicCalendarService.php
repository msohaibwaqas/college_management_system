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
                    ->where(function ($d) use ($date) {
                        $d->where(function ($range) use ($date) {
                            $range->whereDate('holiday_date', '<=', $date)
                                  ->where(function ($end) use ($date) {
                                      $end->whereDate('holiday_end_date', '>=', $date)
                                          ->orWhere(function ($single) use ($date) {
                                              $single->whereNull('holiday_end_date')
                                                     ->whereDate('holiday_date', $date);
                                          });
                                  });
                        })->orWhere(function ($sched) use ($date) {
                            $sched->whereNull('holiday_date')
                                  ->whereDate('scheduled_at', $date);
                        });
                    });
            })->orWhere(function ($sub) use ($date) {
                $sub->where(function ($kw) {
                    $kw->where('title', 'LIKE', '%holiday%')
                       ->orWhere('title', 'LIKE', '%vacation%')
                       ->orWhere('title', 'LIKE', '%off day%')
                       ->orWhere('title', 'LIKE', '%break%');
                })
                ->where(function ($dt) use ($date) {
                    $dt->where(function ($range) use ($date) {
                        $range->whereDate('holiday_date', '<=', $date)
                              ->where(function ($end) use ($date) {
                                  $end->whereDate('holiday_end_date', '>=', $date)
                                      ->orWhere(function ($single) use ($date) {
                                          $single->whereNull('holiday_end_date')
                                                 ->whereDate('holiday_date', $date);
                                      });
                              });
                    })->orWhereDate('scheduled_at', $date);
                });
            });
        })
        ->orderByDesc('is_holiday')
        ->orderByDesc('holiday_end_date')
        ->latest('id')
        ->first();

        if ($holidayAnnouncement) {
            if ($holidayAnnouncement->isVacationRange()) {
                $startFmt = $holidayAnnouncement->holiday_date->format('d M Y');
                $endFmt = $holidayAnnouncement->holiday_end_date->format('d M Y');
                return [true, "Announced Vacation: {$holidayAnnouncement->title} ({$startFmt} to {$endFmt})"];
            }

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
