<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class DeadTreeApproved extends Notification
{
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
    return [
        'message' => "Your dead tree report for {$this->request->tree_code} has been approved.",
        'url' => route('dead-tree-requests.show', $this->request->id),
    ];
    }
}
