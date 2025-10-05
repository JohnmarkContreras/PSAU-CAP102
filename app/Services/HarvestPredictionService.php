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
    private const MIN_DBH_CM = 10.0;   // configurable thresholds
    private const MIN_HEIGHT_M = 2.0;
    private const TIMEOUT_SECONDS = 60;

    public function predictAllTrees()
    {
        $trees = Tree::all();
        $results = [];

        foreach ($trees as $tree) {
            // Skip trees without sufficient DBH/Height if data available via latest TreeData or Tree attributes
            if (! $this->meetsSizeThreshold($tree)) {
                $results[$tree->code] = $this->errorResult($tree->code, 'Tree below DBH/Height thresholds');
                continue;
            }

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

    private function meetsSizeThreshold(Tree $tree): bool
    {
        // Try TreeData latest
        $treeData = \App\TreeData::whereHas('treeCode', function ($q) use ($tree) {
            $q->where('code', $tree->code);
        })->latest('id')->first();

        $dbhCm = null;
        $heightM = null;

        if ($treeData) {
            // Source may be inches/feet; normalize to metric if needed
            $dbhCm = is_null($treeData->dbh) ? null : (float) $treeData->dbh; // assume cm already based on model
            $heightM = is_null($treeData->height) ? null : (float) $treeData->height; // assume meters
        } else {
            // Fallback from Tree model if available
            $dbhCm = property_exists($tree, 'stem_diameter') ? (float) $tree->stem_diameter : null;
            $heightM = property_exists($tree, 'height') ? (float) $tree->height : null;
        }

        if ($dbhCm === null || $heightM === null) {
            // If we do not have measurements, allow training but flag in result message later
            return true;
        }

        $minDbh = (float) config('services.harvest.min_dbh_cm', self::MIN_DBH_CM);
        $minHeight = (float) config('services.harvest.min_height_m', self::MIN_HEIGHT_M);
        return $dbhCm >= $minDbh && $heightM >= $minHeight;
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
        $order = config('services.harvest.sarima_order', '4,1,4');
        $seasonal = config('services.harvest.sarima_seasonal', '0,1,0,12');

        $process = new Process([
            $python, $script, $csvPath, 
            '--order', $order, 
            '--seasonal', $seasonal
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