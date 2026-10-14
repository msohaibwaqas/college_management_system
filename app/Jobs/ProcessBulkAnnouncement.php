<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\Student;
use App\Models\Staff;
use App\Models\NotificationLog;
use App\Notifications\SystemNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBulkAnnouncement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $announcement;
    public $channels;

    public function __construct(Announcement $announcement, array $channels)
    {
        $this->announcement = $announcement;
        $this->channels = $channels;
    }

    public function handle(): void
    {
        $groups = explode(',', $this->announcement->recipient_group);
        $recipients = collect();

        if (in_array('students', $groups)) {
            $recipients = $recipients->merge(Student::where('status', 'active')->get());
        }
        
        if (in_array('teaching_staff', $groups)) {
            $recipients = $recipients->merge(Staff::teaching()->active()->get());
        }

        if (in_array('non_teaching_staff', $groups)) {
            $recipients = $recipients->merge(Staff::nonTeaching()->active()->get());
        }

        $channelString = implode(',', $this->channels);
        $deliveryChannels = [];

        if (in_array('email', $this->channels)) $deliveryChannels[] = 'mail';
        if (in_array('in-app', $this->channels)) $deliveryChannels[] = 'database';
        if (in_array('sms', $this->channels)) $deliveryChannels[] = \App\Channels\SmsChannel::class;

        if (empty($deliveryChannels)) return;

        foreach ($recipients as $recipient) {
            $log = NotificationLog::create([
                'recipient_id' => $recipient->id,
                'recipient_type' => get_class($recipient),
                'channel' => $channelString,
                'type' => 'BULK_ANNOUNCEMENT',
                'message' => $this->announcement->body,
                'status' => 'pending',
                'attempts' => 0
            ]);

            try {
                $recipient->notify(new SystemNotification(
                    $this->announcement->title, 
                    $this->announcement->body, 
                    $deliveryChannels, 
                    $log->id
                ));
            } catch (\Exception $e) {
                Log::error("Failed to notify recipient {$recipient->id}: " . $e->getMessage());
                $log->update(['status' => 'failed', 'failed_reason' => $e->getMessage()]);
            }
        }

        $this->announcement->update(['sent_at' => now()]);
    }
}
