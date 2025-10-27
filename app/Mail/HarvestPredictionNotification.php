<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\HarvestPrediction;
use Carbon\Carbon;

class HarvestPredictionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $prediction;

    public function __construct(HarvestPrediction $prediction)
    {
        $this->prediction = $prediction;
    }

    public function build()
    {
        return $this->subject('Your Harvest Prediction is Ready')
                    ->view('emails.harvest_prediction');
    }
}