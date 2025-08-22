<?php

// app/Console/Commands/PredictHarvests.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tree;
use App\HarvestPrediction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PredictHarvests extends Command
{
    protected $signature = 'harvest:predict';
    protected $description = 'Automatically predict harvests for all trees';

    public function handle()
    {
        $python = env('PYTHON_BIN', 'python');
        $script = base_path('scripts/sarima_predict.py');

        foreach (Tree::all() as $tree) {
            $rows = $tree->harvests()->select('harvest_date','harvest_weight_kg')->orderBy('harvest_date')->get();
            if ($rows->count() < 6) continue;

            $csv = "harvest_date,harvest_weight_kg\n";
            foreach ($rows as $r) {
                $csv .= "{$r->harvest_date},{$r->harvest_weight_kg}\n";
            }

            $path = "harvest_data/{$tree->code}.csv";
            Storage::disk('local')->put($path, $csv);
            $full = storage_path("app/$path");

            $process = new Process([$python, $script, $full, '--order', '4,1,4', '--seasonal', '0,1,0,12']);
            $process->run();

            if (!$process->isSuccessful()) continue;

            $out = json_decode(trim($process->getOutput()), true);
            if (!$out || !isset($out['predicted_quantity'], $out['predicted_date'])) continue;

            HarvestPrediction::updateOrCreate(
                ['code' => $tree->code, 'predicted_date' => $out['predicted_date']],
                [
                    'predicted_quantity' => $out['predicted_quantity'],
                    // Optional: save totals if you want
                    'total_harvest'      => $out['total_harvest'] ?? null,
                    'average_harvest'    => $out['average_harvest'] ?? null,
                ]
            );
        }

        $this->info("Harvest predictions updated.");
    }
}

