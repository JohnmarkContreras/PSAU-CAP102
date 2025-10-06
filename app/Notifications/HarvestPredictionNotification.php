<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\NexmoMessage; // For Twilio/Nexmo SMS
use Illuminate\Support\Facades\Log;

class HarvestPredictionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $prediction;

    public function __construct($prediction)
    {
        $this->prediction = $prediction;
    }

    /**
     * Determine delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'nexmo']; // or ['mail', 'twilio'] if you’re using Twilio SDK
    }

    /**
     * Email notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🌱 Tamarind Harvest Prediction Reminder')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('A new harvest prediction has been generated.')
            ->line('Predicted date: ' . $this->prediction->predicted_date)
            ->line('Predicted yield: ' . $this->prediction->predicted_quantity . ' kg')
            ->action('View Details', url('/harvest-management'))
            ->line('Thank you for using the Tamarind Monitoring System!');
    }

    /**
     * SMS notification (Twilio/Nexmo).
     */
    public function toNexmo($notifiable)
    {
        $msg = '🌱 Harvest prediction: ' .
            $this->prediction->predicted_quantity . ' kg on ' .
            $this->prediction->predicted_date;

        return (new NexmoMessage)
            ->content($msg);
    }

    /**
     * Optional: store in database (notifications table).
     */
    public function toArray($notifiable)
    {
        return [
            'predicted_date' => $this->prediction->predicted_date,
            'predicted_quantity' => $this->prediction->predicted_quantity,
            'message' => 'Harvest prediction generated.',
        ];
    }
}
