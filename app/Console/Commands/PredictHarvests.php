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
        foreach (TreeCode::all() as $tree) {
        $prediction = $service->predictForTree($tree);
        if ($prediction) {
            $this->info("Predicted for {$tree->code}");
            $this->notifyUser($prediction);
        }
    }
    }
}

