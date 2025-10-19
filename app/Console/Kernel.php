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
        \App\Console\Commands\SendHarvestReminders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
        protected function schedule(Schedule $schedule)
        {
        // $schedule->command('harvest:reminders')->daily()->at('01:00');
        // $schedule->command('harvest:predict')->daily()->at('01:00');
        // $schedule->command('backup:hdd')->daily()->at('01:00');
        }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(app_path('Console/Commands'));
    }
}
