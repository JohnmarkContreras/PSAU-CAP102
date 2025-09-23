<?php

namespace App\Notifications;

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeotagStatusChanged extends Notification
{
    use Queueable;

    public $status;
    public $geotagId;

    public function __construct($status, $geotagId)
    {
        $this->status = $status;
        $this->geotagId = $geotagId;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "Your geotag has been {$this->status}.",
            'geotag_id' => $this->geotagId,
            'status' => $this->status,
        ];
    }
}
