<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResultMarkChanged extends Notification
{
    use Queueable;

    public $details;

    /**
     * Create a new notification instance.
     * 
     * @param array $details Must contain 'student_name', 'subject', 'user_name', 'old_theory', 'new_theory', 'old_practical', 'new_practical'
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mark_change',
            'title' => 'Mark Change Alert',
            'message' => "Student {$this->details['student_name']}'s {$this->details['subject']} marks changed by {$this->details['user_name']}. Old Theory: {$this->details['old_theory']} -> New Theory: {$this->details['new_theory']}, Old Practical: {$this->details['old_practical']} -> New Practical: {$this->details['new_practical']}",
            'student_name' => $this->details['student_name'],
            'subject' => $this->details['subject'],
            'user_name' => $this->details['user_name'],
            'prev_theory' => $this->details['old_theory'],
            'new_theory' => $this->details['new_theory'],
            'prev_practical' => $this->details['old_practical'],
            'new_practical' => $this->details['new_practical'],
        ];
    }
}
