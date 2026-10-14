<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Notifications\SystemNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch a notification using a registered template
     *
     * @param string $type The template type (e.g. 'N-01')
     * @param \Illuminate\Database\Eloquent\Model $recipient The recipient model (Student, Staff, User)
     * @param array $data Data for placeholder mapping
     * @return bool
     */
    public function dispatch($type, $recipient, array $data = [])
    {
        $template = NotificationTemplate::where('type', $type)->where('is_active', true)->first();

        if (!$template) {
            Log::warning("Notification template [{$type}] not found or inactive.");
            return false;
        }

        $subject = $this->replacePlaceholders($template->subject ?? '', $data);
        $body = $this->replacePlaceholders($template->body ?? '', $data);

        // Map template channels ('sms,email,in-app') to Laravel channels
        $templateChannels = array_map('trim', explode(',', strtolower($template->channel)));
        $deliveryChannels = [];

        if (in_array('email', $templateChannels)) $deliveryChannels[] = 'mail';
        if (in_array('in-app', $templateChannels) || in_array('database', $templateChannels)) $deliveryChannels[] = 'database';
        if (in_array('sms', $templateChannels)) $deliveryChannels[] = \App\Channels\SmsChannel::class;

        if (empty($deliveryChannels)) {
            Log::warning("Notification template [{$type}] has no valid channels.");
            return false;
        }

        // Create log entry
        $log = NotificationLog::create([
            'recipient_id' => $recipient->id,
            'recipient_type' => get_class($recipient),
            'channel' => implode(',', $templateChannels),
            'type' => $type,
            'message' => $body,
            'status' => 'pending',
            'attempts' => 0
        ]);

        try {
            // Trigger Laravel notification
            $recipient->notify(new SystemNotification($subject, $body, $deliveryChannels, $log->id));
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to enqueue notification {$type}: " . $e->getMessage());
            $log->update(['status' => 'failed', 'failed_reason' => $e->getMessage()]);
            return false;
        }
    }

    private function replacePlaceholders($text, $data)
    {
        if (empty($text)) return $text;

        foreach ($data as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }
}
