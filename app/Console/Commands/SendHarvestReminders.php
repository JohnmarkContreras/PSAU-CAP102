<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\User;
use App\HarvestPrediction;
use Illuminate\Support\Facades\Mail;
use App\Mail\HarvestReminderMail;
use Carbon\Carbon;

class SendHarvestReminders extends Command
{

    protected $signature = 'harvest:reminders'; // 👈 This must match your artisan call
    protected $description = 'Send harvest reminder emails to users';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = now();
        $predictions = HarvestPrediction::whereDate('predicted_date', $today->addDays(30))
            ->orWhereDate('predicted_date', $today->addDays(7))
            ->orWhereDate('predicted_date', $today->addDays(1))
            ->get();

        foreach ($predictions as $prediction) {
            Mail::to($prediction->user->email)->send(new HarvestReminderMail($prediction));
        }
    }
}
