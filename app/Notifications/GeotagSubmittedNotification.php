<?php

namespace App\Notifications;

use App\PendingGeotag;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeotagSubmittedNotification extends Notification
{
    use Queueable;

    protected $geotag;

    public function __construct(PendingGeotag $geotag)
    {
        $this->geotag = $geotag;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => "New geotag submitted by {$this->geotag->user->name}",
            'geotag_id' => $this->geotag->id,
            'code' => $this->geotag->code,
        ];
    }
}
