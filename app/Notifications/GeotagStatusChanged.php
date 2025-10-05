<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GeotagStatusChanged extends Notification
{
    use Queueable;

    public $status;
    public $geotagId;
    public $reason;

    public function __construct($status, $geotagId, $reason = 'N/A')
    {
        $this->status = $status;
        $this->geotagId = $geotagId;
        $this->reason = $reason ?: 'N/A';
    }

    /**
     * Define delivery channels (must include database for DB notifications).
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Store notification in the database.
     */
    public function toDatabase($notifiable)
    {
        return [
            'status' => $this->status,
            'geotag_id' => $this->geotagId,
            'reason' => $this->reason,
            'message'   => $this->getMessage(), //human-readable message
        ];
    }

    private function getMessage()
    {
        switch ($this->status) {
            case 'pending':
                return "A new geotag has been submitted and is awaiting approval.";
            case 'approved':
                return "Your geotag #{$this->geotagId} has been approved.";
            case 'rejected':
                return "Your geotag #{$this->geotagId} has been rejected. Reason: {$this->reason}";
            default:
                return "Geotag status changed.";
        }
    }

    /**
     * Convert notification to array (used in broadcasting).
     */
    public function toArray($notifiable)
    {
        return [
            'status' => $this->status,
            'geotag_id' => $this->geotagId,
            'reason' => $this->reason,
        ];
    }
}
