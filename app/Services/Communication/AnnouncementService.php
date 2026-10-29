<?php

namespace App\Services\Communication;

use App\Jobs\ProcessBulkAnnouncement;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    /**
     * Create an announcement and dispatch bulk messaging job.
     */
    public function createAnnouncement(array $validated, ?int $userId = null): Announcement
    {
        return DB::transaction(function () use ($validated, $userId) {
            $isHoliday = !empty($validated['is_holiday']);

            $announcement = Announcement::create([
                'title'           => $validated['title'],
                'body'            => $validated['body'],
                'recipient_group' => implode(',', $validated['recipient_group']),
                'is_holiday'      => $isHoliday,
                'holiday_date'    => $isHoliday ? ($validated['holiday_date'] ?? null) : null,
                'holiday_end_date'=> $isHoliday ? ($validated['holiday_end_date'] ?? null) : null,
                'scheduled_at'    => $validated['scheduled_at'] ?? null,
                'created_by'      => $userId,
            ]);

            // Dispatch background processing job
            dispatch(new ProcessBulkAnnouncement($announcement, $validated['channels']));

            return $announcement;
        });
    }

    /**
     * Physically delete announcement and associated recipient log entries.
     */
    public function deleteAnnouncement(Announcement $announcement): void
    {
        DB::transaction(function () use ($announcement) {
            $announcement->recipients()->delete();
            $announcement->forceDelete();
        });
    }
}
