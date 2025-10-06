<?php

namespace App\Notifications\Channels;

use Twilio\Rest\Client;

class TwilioChannel
{
    public function send($notifiable, $notification)
    {
        if (!method_exists($notification, 'toTwilio')) {
            return;
        }

        $notification->toTwilio($notifiable);
    }
}
