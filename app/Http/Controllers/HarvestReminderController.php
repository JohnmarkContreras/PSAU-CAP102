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
        $predictions = HarvestPrediction::orderBy('predicted_date')->get(['code','predicted_date','predicted_quantity']);
        $users = User::all();

        foreach ($users as $user) {
            Mail::to($user->email)->send(
                new HarvestReminderMail($predictions, $user->name)
            );
        }

        return back()->with('status', 'Reminders sent to all users!');
    }

        public function sendSMS($number, $message)
    {
        $response = Http::asForm()->post('https://api.semaphore.co/api/v4/messages', [
            'apikey' => 'f6ca4e50cbb5d513ae6053ce10251a26',
            'number' => $number,
            'message' => $message,
            'sendername' => 'LanWired',
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json(),
            ];
        }

        return [
            'success' => false,
            'error' => $response->body(),
        ];
    }

    //  This sends the SMS to all users with the "user" role
    public function sendSMSToAllUsers()
    {
        $users = User::where('account_id', 2)->get();
        $message = 'Hello! This is a message from Tamarind RDE.';

        $results = [];

        foreach ($users as $user) {
            // make sure they have a phone number
            if (!empty($user->number)) {
                $results[] = [
                    'user' => $user->name,
                    'number' => $user->number,
                    'result' => $this->sendSMS($user->number, $message),
                ];
            }
        }

        return response()->json([
            'sent_to' => $users->count(),
            'results' => $results,
        ]);
    }
}