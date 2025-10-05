<?php

// app/Console/Commands/PredictHarvests.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HarvestPredictionService;

class PredictHarvests extends Command
{
    protected $signature = 'harvest:predict';
    protected $description = 'Automatically predict harvests for all trees';

    public function handle()
    {
        /** @var HarvestPredictionService $service */
        $service = app(HarvestPredictionService::class);
        $results = $service->predictAllTrees();
        $ok = collect($results)->where('ok', true)->count();
        $this->info("Harvest predictions updated for {$ok} tree codes.");
    }
}

