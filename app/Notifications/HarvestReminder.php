<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\HarvestPrediction;

class HarvestScheduleNotification extends Notification
{
    use Queueable;

    protected $prediction;

    public function __construct(HarvestPrediction $prediction)
    {
        $this->prediction = $prediction;
    }

    public function via($notifiable)
    {
        // You can send via 'mail', 'database', 'broadcast', etc.
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Upcoming Tamarind Harvest')
            ->greeting("Hello {$notifiable->name},")
            ->line("Your tree (Code: {$this->prediction->code}) is expected to be ready for harvest around {$this->prediction->predicted_date}.")
            ->line("Predicted yield: {$this->prediction->predicted_quantity} kg.")
            ->action('View Details', url('/harvest-management'))
            ->line('Please prepare for harvest!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'tree_code' => $this->prediction->code,
            'predicted_date' => $this->prediction->predicted_date,
            'predicted_quantity' => $this->prediction->predicted_quantity,
            'message' => "Your tree is expected to be ready for harvest on {$this->prediction->predicted_date}.",
        ];
    }
}
