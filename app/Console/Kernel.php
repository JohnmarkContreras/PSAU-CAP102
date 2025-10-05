<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\HarvestPrediction;
use App\Notifications\HarvestReminder;
use App\User;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
    {
        $schedule->call(function () {
            $target = now()->startOfDay()->addDays(7)->toDateString();
            $predictions = HarvestPrediction::whereDate('predicted_date', $target)->get();

            if ($predictions->isEmpty()) {
                return;
            }

            $recipients = class_exists(\Spatie\Permission\Models\Role::class)
                ? \App\User::role(['admin','superadmin'])->get()
                : \App\User::all();

            foreach ($predictions as $prediction) {
                foreach ($recipients as $user) {
                    $user->notify(new HarvestReminder($prediction));
                }
            }
        })->dailyAt('08:00'); // Manila time from config/app.php
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
