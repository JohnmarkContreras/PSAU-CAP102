<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Tree;
use App\Feedback;

class FeedbackStatusUpdated extends Notification
{
    use Queueable;

    public $feedback;
    public $tree;
    
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
        if ($notifiable->hasRole(['admin','superadmin'])) {
        return [
            'message' => 'A new geotagged tree was added and is pending approval.',
            'tree_id' => $this->tree->code,
            'user'    => $this->tree->user->name ?? 'Unknown',
        ];
    }
        return [
            'feedback_id' => $this->feedback->id,
            'status'      => $this->feedback->status,
            'message'     => "Your feedback has been updated to {$this->feedback->status}.",
        ];
    }

}