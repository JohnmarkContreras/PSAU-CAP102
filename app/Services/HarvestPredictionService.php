<?php

namespace App\Services;

use App\Tree;
use App\HarvestPrediction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HarvestPredictionService
{
    private const MIN_RECORDS_REQUIRED = 6;
    private const TIMEOUT_SECONDS = 60;

    public function predictAllTrees()
    {
        $trees = Tree::all();
        $results = [];

        foreach ($trees as $tree) {
            $results[$tree->code] = $this->predictForTree($tree);
        }

        return $results;
    }

    private function predictForTree(Tree $tree)
    {
        try {
            $harvests = $this->getTreeHarvests($tree);

            if ($harvests->count() < self::MIN_RECORDS_REQUIRED) {
                return $this->errorResult($tree->code, 'Need at least 6 records to forecast.');
            }

            $csvPath = $this->generateCsvFile($tree, $harvests);
            $prediction = $this->runPredictionScript($csvPath);

            if (!$this->isValidPrediction($prediction)) {
                return $this->errorResult($tree->code, 'Invalid prediction output from Python.');
            }

            $savedPrediction = $this->savePrediction($tree->code, $prediction);

            return $this->successResult($tree->code, $savedPrediction);

        } catch (\Throwable $e) {
            return $this->errorResult($tree->code, $e->getMessage());
        }
    }

    private function getTreeHarvests(Tree $tree)
    {
        return $tree->harvests()
            ->select('harvest_date', 'harvest_weight_kg')
            ->orderBy('harvest_date')
            ->get();
    }

    private function generateCsvFile(Tree $tree, $harvests)
    {
        $csv = "harvest_date,harvest_weight_kg\n";
        
        foreach ($harvests as $harvest) {
            $csv .= "{$harvest->harvest_date},{$harvest->harvest_weight_kg}\n";
        }

        $path = "harvest_data/{$tree->code}.csv";
        Storage::disk('local')->put($path, $csv);

        return storage_path("app/$path");
    }

    private function runPredictionScript($csvPath)
    {
        $script = base_path('scripts/sarima_predict.py');
        $python = env('PYTHON_BIN', 'python');

        $process = new Process([
            $python, $script, $csvPath, 
            '--order', '4,1,4', 
            '--seasonal', '0,1,0,12'
        ]);

        $process->setTimeout(self::TIMEOUT_SECONDS);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return json_decode(trim($process->getOutput()), true);
    }

    private function isValidPrediction($prediction)
    {
        return $prediction 
            && isset($prediction['predicted_quantity']) 
            && isset($prediction['predicted_date']);
    }

    private function savePrediction($treeCode, $prediction)
    {
        return HarvestPrediction::updateOrCreate(
            ['code' => $treeCode, 'predicted_date' => $prediction['predicted_date']],
            ['predicted_quantity' => $prediction['predicted_quantity']]
        );
    }

    private function successResult($code, $prediction)
    {
        return [
            'code' => $code,
            'ok' => true,
            'predicted_date' => $prediction->predicted_date,
            'predicted_quantity' => (float) $prediction->predicted_quantity
        ];
    }

    private function errorResult($code, $message)
    {
        return [
            'code' => $code,
            'ok' => false,
            'message' => $message
        ];
    }
}