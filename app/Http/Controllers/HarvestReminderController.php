<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\HarvestReminderMail;
use App\User;
use App\HarvestPrediction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class HarvestReminderController extends Controller
{
    public function sendReminders()
    {
        $predictions = HarvestPrediction::orderBy('predicted_date')
            ->get(['code','predicted_date','predicted_quantity']);

        $users = User::paginate(50);

        foreach ($users as $user) {
            // 1. Send Email
            Mail::to($user->email)->send(
                new HarvestReminderMail($predictions, $user->name)
            );

            // 2. Send SMS (only if user has a number)
            if (!empty($user->number)) {
                $message = "Hello {$user->name}, you have new harvest predictions. "
                        . "Check your dashboard for details.";
                $this->sendSMS($user->number, $message);
            }
        }

        return back()->with('status', 'Reminders (Email + SMS) sent to all users!');
    }

    public function sendSMS($number, $message)
    {
        $response = Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
        'apikey' => 'f6ca4e50cbb5d513ae6053ce10251a26',
        'number' => $number,
        'message' => $message,
        'sendername' => 'LanWired',
    ]);

        return $response->successful()
            ? ['success' => true, 'response' => $response->json()]
            : ['success' => false, 'error' => $response->body()];
    }

}