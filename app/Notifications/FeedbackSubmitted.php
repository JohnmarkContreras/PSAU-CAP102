<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class FeedbackSubmitted extends Notification
{
    use Queueable;

    public $feedback;

    public function __construct($feedback)
    {
        $this->feedback = $feedback;
    }

    // Deliver via database
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'A new feedback has been submitted and is pending review.',
            'feedback_id' => $this->feedback->id,
            'user' => $this->feedback->user->name ?? 'Unknown',
            'status' => $this->feedback->status,
        ];
    }
}

