<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\HarvestPredictionNotification;

class NotificationService
{
    public function sendHarvestNotification($prediction)
    {
        // Email
        $user = $prediction->user;
        if ($user && $user->email) {
            Mail::to($user->email)->send(new HarvestPredictionNotification($prediction));
        }

        // SMS
        if ($user && $user->phone) {
            $client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $client->messages->create(
                $user->phone,
                [
                    'from' => env('TWILIO_FROM'),
                    'body' => "🌾 Harvest Prediction Update:
Tree {$prediction->tree->code} - Expected yield: {$prediction->predicted_yield} kg. 
Check your dashboard for details."
                ]
            );
        }
    }
}
