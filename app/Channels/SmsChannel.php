<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $mobileNumber = $notifiable->routeNotificationFor('sms') ?? $notifiable->mobile ?? $notifiable->contact_number ?? null;

        if (!$mobileNumber) {
            Log::warning("No mobile number found for notifiable.", ['notifiable' => get_class($notifiable), 'id' => $notifiable->id]);
            $this->updateLogStatus($notification->logId, 'failed', 'No mobile number found');
            return;
        }

        try {
            // Here you would integrate with Jazz, Ufone, Zong, or MSG91 API
            // For now, we simulate a successful API call
            Log::info("Sending SMS to {$mobileNumber}: {$message}");
            
            // Example real API call placeholder:
            // Http::post('https://api.smsgateway.com/send', [
            //     'api_key' => config('services.sms.key'),
            //     'to' => $mobileNumber,
            //     'message' => $message
            // ]);

            $this->updateLogStatus($notification->logId, 'sent');

        } catch (\Exception $e) {
            Log::error("SMS sending failed: " . $e->getMessage());
            $this->updateLogStatus($notification->logId, 'failed', $e->getMessage());
        }
    }

    private function updateLogStatus($logId, $status, $reason = null)
    {
        if (!$logId) return;
        
        $log = NotificationLog::find($logId);
        if ($log) {
            $log->status = $status;
            if ($status === 'sent') {
                $log->sent_at = now();
            }
            if ($reason) {
                $log->failed_reason = $reason;
            }
            $log->increment('attempts');
            $log->save();
        }
    }
}
