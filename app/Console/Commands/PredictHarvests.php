<?php

// app/Console/Commands/PredictHarvests.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HarvestPredictionService;
use App\User;
use App\Mail\HarvestReminderMail;
use Illuminate\Support\Facades\Mail;
use App\HarvestPrediction;
use Carbon\Carbon;

class PredictHarvests extends Command
{
    protected $signature = 'harvest:predict';
    protected $description = 'Automatically predict harvests for all trees';

    // public function handle()
    // {
    //     /** @var HarvestPredictionService $service */
    //     $service = app(HarvestPredictionService::class);
    //     $results = $service->predictAllTrees();
    //     $ok = collect($results)->where('ok', true)->count();
    //     $this->info("Harvest predictions updated for {$ok} tree codes.");

    //     // Show predictions in console
    //     $predictions = HarvestPrediction::orderBy('predicted_date')->get(['code', 'predicted_date', 'predicted_quantity']);

    //     $this->table(
    //         ['Tree Code', 'Predicted Harvest Date', 'Predicted Quantity'],
    //         $predictions->map(fn($p) => [
    //             $p->code,
    //             Carbon::parse($p->predicted_date)->format('F j, Y'),
    //             $p->predicted_quantity . ' kg',
    //         ])->toArray()
    //     );
    // }
    public function handle()
    {
        // Only run during tamarind season: December - March
        $currentMonth = now()->month;
        if (!in_array($currentMonth, [12, 1, 2, 3])) {
            $this->info('Outside tamarind season. Predictions not updated.');
            return;
        }

        /** @var HarvestPredictionService $service */
        $service = app(HarvestPredictionService::class);
        $results = $service->predictAllTrees();
        $ok = collect($results)->where('ok', true)->count();
        $this->info("Harvest predictions updated for {$ok} tree codes.");

        // Show predictions in console
        $predictions = HarvestPrediction::orderBy('predicted_date')->get(['code', 'predicted_date', 'predicted_quantity']);

        $this->table(
            ['Tree Code', 'Predicted Harvest Date', 'Predicted Quantity'],
            $predictions->map(fn($p) => [
                $p->code,
                Carbon::parse($p->predicted_date)->format('F j, Y'),
                $p->predicted_quantity . ' kg',
            ])->toArray()
        );
    }
}
