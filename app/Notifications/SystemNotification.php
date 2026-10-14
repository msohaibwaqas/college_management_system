<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $subject;
    public $body;
    public $channels;
    public $logId;

    /**
     * Create a new notification instance.
     */
    public function __construct($subject, $body, array $channels, $logId)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->channels = $channels;
        $this->logId = $logId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->body);
    }

    /**
     * Get the array representation of the notification (for Database channel).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'body' => $this->body,
            'log_id' => $this->logId,
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return $this->body;
    }
}
