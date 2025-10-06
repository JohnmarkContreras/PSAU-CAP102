<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HarvestPredictionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $prediction;

    public function __construct($prediction)
    {
        $this->prediction = $prediction;
    }

    public function build()
    {
        return $this->subject('New Harvest Prediction Available')
                    ->view('emails.harvest_prediction')
                    ->with([
                        'treeCode' => $this->prediction->tree->code ?? 'Unknown Tree',
                        'predictedYield' => $this->prediction->predicted_yield,
                        'predictionDate' => $this->prediction->created_at->format('F d, Y'),
                    ]);
    }
}
