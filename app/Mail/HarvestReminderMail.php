<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HarvestReminderMail extends Mailable
{
    public $predictions;
    public $userName;

    public function __construct($predictions, $userName)
    {
        $this->predictions = $predictions;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject("Reminder: Upcoming Harvest Predictions")
                    ->view('emails.harvest_reminder');
    }
}
