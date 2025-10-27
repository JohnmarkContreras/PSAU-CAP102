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

    protected $signature = 'harvest:reminders';
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
        // Only run during tamarind season: December - March
        $currentMonth = now()->month;
        if (!in_array($currentMonth, [12, 1, 2, 3])) {
            $this->info('Outside tamarind season. Reminders not sent.');
            return;
        }

        $reminderDates = [
            now()->addMonth()->toDateString(),
            now()->addWeeks(2)->toDateString(),
            now()->addWeek()->toDateString(),
            now()->addDay()->toDateString(),
        ];

        $predictions = HarvestPrediction::whereIn('predicted_date', $reminderDates)->get();

        foreach ($predictions as $prediction) {
            Mail::to($prediction->user->email)->send(new HarvestReminderMail($prediction));
            $this->info("Reminder sent for tree {$prediction->code}");
        }

        $this->info(count($predictions) . ' harvest reminder(s) sent.');
    }
}