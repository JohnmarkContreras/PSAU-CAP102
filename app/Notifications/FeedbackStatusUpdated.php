<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Feedback;

class FeedbackStatusUpdated extends Notification
{
    use Queueable;

    public $feedback;

    public function __construct(Feedback $feedback)
    {
        $this->feedback = $feedback;
    }

    public function via($notifiable)
    {
        return ['database']; //in-app notifications
    }

    public function toDatabase($notifiable)
    {
        return [
            'feedback_id' => $this->feedback->id,
            'status'      => $this->feedback->status,
            'message'     => "Your feedback has been updated to {$this->feedback->status}.",
        ];
    }

}