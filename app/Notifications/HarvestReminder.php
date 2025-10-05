<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use App\HarvestPrediction;

class HarvestReminder extends Notification
{
    use Queueable;

    public HarvestPrediction $prediction;

    public function __construct(HarvestPrediction $prediction)
    {
        $this->prediction = $prediction;
    }

    public function via($notifiable)
    {
        $channels = ['mail', 'database'];

        // Enable SMS via Twilio if configured
        if (config('services.twilio.sid') && config('services.twilio.from')) {
            $channels[] = TwilioChannel::class;
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Upcoming Tamarind Harvest')
            ->greeting("Hello {$notifiable->name},")
            ->line("Tree Code: {$this->prediction->code}")
            ->line("Expected harvest window: around {$this->prediction->predicted_date}.")
            ->line("Predicted yield: " . number_format((float) $this->prediction->predicted_quantity, 2) . " kg.")
            ->action('Open Harvest Manager', url('/harvest-management'))
            ->line('This is an automated reminder.');
    }

    public function toTwilio($notifiable)
    {
        $date = $this->prediction->predicted_date;
        $qty  = number_format((float) $this->prediction->predicted_quantity, 2);
        $code = $this->prediction->code;

        return (new TwilioSmsMessage())
            ->content("Tamarind harvest: Tree {$code} ~{$date} (est {$qty} kg)");
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
