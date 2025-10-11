<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HarvestReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $daysBefore;
    public $userName;

    public function __construct($daysBefore, $userName)
        {
            $this->daysBefore = $daysBefore;
            $this->userName = $userName;
        }


        /**
         * Build the message.
         *
         * @return $this
         */
    public function build()
        {
            return $this->subject("Reminder: Harvest in {$this->daysBefore} day(s)")
                        ->view('emails.harvest_reminder');
        }
    }
