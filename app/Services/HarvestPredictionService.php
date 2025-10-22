<?php

namespace App\Services;

use App\TreeCode;
use App\Harvest;
use App\HarvestPrediction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Mail\HarvestPredictionNotification;
use Illuminate\Support\Facades\Mail;
use App\User;

class HarvestPredictionService
{
    private const MIN_RECORDS_REQUIRED = 6;
    private const MIN_DBH_CM = 10.0;
    private const MIN_HEIGHT_M = 2.0;
    private const TIMEOUT_SECONDS = 120;

    public function predictAllTrees(bool $yieldingOnly = false)
    {
        $codesWithHarvests = Harvest::select('code')
            ->whereNotNull('harvest_date')
            ->distinct()
            ->pluck('code')
            ->toArray();

        if (empty($codesWithHarvests)) {
            return [
                'ok' => false,
                'results' => [],
                'message' => 'No trees with actual harvest records found.'
            ];
        }

        $codes = TreeCode::orderBy('code')
            ->whereIn('code', $codesWithHarvests)
            ->paginate(50);

        $results = [];

        foreach ($codes as $tc) {
            $code = $tc->code;

            if ($yieldingOnly) {
                if (! $this->isYieldingByCode($code)) {
                    $results[$code] = $this->errorResult($code, 'Not yielding (below DBH/Height thresholds)');
                    continue;
                }
            } else {
                if (! $this->meetsSizeThresholdByCode($code)) {
                    $results[$code] = $this->errorResult($code, 'Tree below DBH/Height thresholds');
                    continue;
                }
            }

            $results[$code] = $this->predictForCode($code);
        }

        return [
            'ok' => true,
            'results' => $results
        ];
    }


    public function predictForCode(string $code)
    {
        try {
            $harvests = $this->getCombinedHarvests($code);

            $numPoints = count($harvests);
            $monthKeys = [];
            $yearKeys = [];
            foreach ($harvests as $h) {
                if (!empty($h['harvest_date'])) {
                    $monthKeys[date('Y-m', strtotime($h['harvest_date']))] = true;
                    $yearKeys[date('Y', strtotime($h['harvest_date']))] = true;
                }
            }
            $distinctMonths = count($monthKeys);
            $distinctYears = count($yearKeys);

            if ($numPoints < self::MIN_RECORDS_REQUIRED && $distinctMonths < self::MIN_RECORDS_REQUIRED && $distinctYears < self::MIN_RECORDS_REQUIRED) {
                return $this->errorResult($code, 'Need ≥6 records (points/months/years) to forecast.');
            }

            $csvPath = $this->generateCsvFileForCode($code, $harvests);
            $predictions = $this->runPredictionScript($csvPath, $code); // Now returns array

            if (!$this->isValidPredictions($predictions)) {
                return $this->errorResult($code, 'Invalid prediction output from Python.');
            }

            // Save all predictions (Jan, Feb, Mar)
            $savedPredictions = [];
            foreach ($predictions as $prediction) {
                $savedPredictions[] = $this->savePrediction($code, $prediction);
            }

            return $this->successResult($code, $savedPredictions);

        } catch (\Throwable $e) {
            return $this->errorResult($code, $e->getMessage());
        }
    }

    private function getCombinedHarvests(string $code): array
    {
        $variants = $this->codeVariants($code);
        $rows = Harvest::where(function ($q) use ($variants) {
                foreach ($variants as $v) {
                    $q->orWhereRaw('LOWER(code) = ?', [mb_strtolower($v)]);
                }
            })
            ->select('harvest_date', 'harvest_weight_kg')
            ->orderBy('harvest_date')
            ->get()
            ->map(function ($r) {
                return [
                    'harvest_date' => (string) $r->harvest_date,
                    'harvest_weight_kg' => (float) $r->harvest_weight_kg,
                ];
            })->toArray();

        $td = \App\TreeData::whereHas('treeCode', function ($q) use ($code) { $q->where('code', $code); })
            ->latest('id')->first();
        if ($td && !empty($td->harvests)) {
            $rows = array_merge($rows, $this->parseHarvestsJson($td->harvests));
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['harvest_date'], $b['harvest_date']);
        });
        return $rows;
    }

    private function codeVariants(string $code): array
    {
        $code = trim($code);
        $low = mb_strtolower($code);
        $variants = [$code];
        
        if (strpos($low, 'sour') === 0) {
            $rest = mb_substr($code, 4);
            $variants[] = 'SOUR' . $rest;
            $variants[] = 'sour' . $rest;
        } elseif (strpos($low, 'sweet') === 0) {
            $rest = mb_substr($code, 5);
            $variants[] = 'SWEET' . $rest;
            $variants[] = 'sweet' . $rest;
        } elseif (strpos($low, 'semi_sweet') === 0 || strpos($low, 'semi-sweet') === 0) {
            $rest = mb_substr($code, 10);
            $variants[] = 'SEMI_SWEET' . $rest;
            $variants[] = 'semi_sweet' . $rest;
            $variants[] = 'SEMI-SWEET' . $rest;
            $variants[] = 'semi-sweet' . $rest;
        }
        
        $pfx = mb_substr($low, 0, 2);
        $rest = mb_substr($code, 2);
        if ($pfx === 'si' || $pfx === 'sl') {
            $variants[] = 'Sl' . $rest;
            $variants[] = 'SI' . $rest;
            $variants[] = 'si' . $rest;
            $variants[] = 'sl' . $rest;
        }
        
        return array_values(array_unique($variants));
    }

    private function meetsSizeThresholdByCode(string $code): bool
    {
        $treeData = \App\TreeData::whereHas('treeCode', function ($q) use ($code) {
            $q->where('code', $code);
        })->latest('id')->first();

        $dbhCm = null;
        $heightM = null;

        if ($treeData) {
            $dbhCm = is_null($treeData->dbh) ? null : (float) $treeData->dbh;
            $heightM = is_null($treeData->height) ? null : (float) $treeData->height;
        }

        if ($dbhCm === null || $heightM === null) {
            return true;
        }

        $minDbh = (float) config('services.harvest.min_dbh_cm', self::MIN_DBH_CM);
        $minHeight = (float) config('services.harvest.min_height_m', self::MIN_HEIGHT_M);
        return $dbhCm >= $minDbh && $heightM >= $minHeight;
    }

    private function isYieldingByCode(string $code): bool
    {
        $treeData = \App\TreeData::whereHas('treeCode', function ($q) use ($code) {
            $q->where('code', $code);
        })->latest('id')->first();

        if (! $treeData) {
            return false;
        }

        $dbhCm = is_null($treeData->dbh) ? null : (float) $treeData->dbh;
        $heightM = is_null($treeData->height) ? null : (float) $treeData->height;
        if ($dbhCm === null || $heightM === null) {
            return false;
        }
        $minDbh = (float) config('services.harvest.min_dbh_cm', self::MIN_DBH_CM);
        $minHeight = (float) config('services.harvest.min_height_m', self::MIN_HEIGHT_M);
        return $dbhCm >= $minDbh && $heightM >= $minHeight;
    }

    private function generateCsvFileForCode(string $code, array $harvests)
    {
        $csv = "harvest_date,harvest_weight_kg\n";
        
        foreach ($harvests as $harvest) {
            $csv .= sprintf("%s,%s\n", $harvest['harvest_date'], $harvest['harvest_weight_kg']);
        }

        $path = "harvest_data/{$code}.csv";
        Storage::disk('local')->put($path, $csv);

        return storage_path("app/$path");
    }

    // CHANGED: Now returns array of predictions
    private function runPredictionScript(string $csvPath, string $treeCode)
    {
        $script = base_path('scripts/sarima_predict.py');
        $pythonPath = base_path('venv/bin/python3');
        
        $order = config('services.harvest.sarima_order', '1,1,1');
        $seasonal = config('services.harvest.sarima_seasonal', '1,1,1,12');
        $months = config('services.harvest.harvest_months', '1,2,3');

        $process = new Process([
            $pythonPath,
            $script, 
            $csvPath,
            '--order', $order,
            '--seasonal', $seasonal,
            '--harvest_months', $months,
        ]);

        $process->setTimeout(self::TIMEOUT_SECONDS);
        $process->run();

        $output = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput());

        if (!$process->isSuccessful()) {
            \Log::error("[SARIMA] Python failed for $csvPath", [
                'stderr' => $errorOutput,
                'stdout' => $output
            ]);
            throw new ProcessFailedException($process);
        }

        $decoded = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$decoded) {
            $baseName = pathinfo($csvPath, PATHINFO_FILENAME);
            $predictionPath = storage_path("app/predictions/{$baseName}_prediction.json");
            @file_put_contents($predictionPath, json_encode(['forecast' => null, 'evaluation' => null], JSON_PRETTY_PRINT));
            \Log::error("[SARIMA] Invalid JSON output for $csvPath", [
                'raw_output' => $output,
                'stderr' => $errorOutput,
                'json_error' => json_last_error_msg(),
            ]);
            return [];
        }

        // CHANGED: Return season_predictions array (all Jan-Mar predictions)
        if (isset($decoded['forecast']['season_predictions'])) {
            return $decoded['forecast']['season_predictions'];
        }

        // Fallback to single prediction for backward compatibility
        if (isset($decoded['forecast'])) {
            return [[
                'predicted_quantity' => $decoded['forecast']['predicted_quantity'] ?? null,
                'predicted_date' => $decoded['forecast']['predicted_date'] ?? null,
            ]];
        }

        return [];
    }

    // CHANGED: Validate array of predictions
private function isValidPredictions($predictions)
{
    if (!is_array($predictions) || empty($predictions)) {
        return false;
    }

    foreach ($predictions as $prediction) {
        if (
            !isset($prediction['predicted_quantity']) || 
            !isset($prediction['predicted_date']) ||
            $prediction['predicted_quantity'] === null || 
            $prediction['predicted_date'] === null
        ) {
            return false;
        }
    }

    return true;
}


    private function savePrediction($treeCode, $prediction)
    {
        return HarvestPrediction::updateOrCreate(
            ['code' => $treeCode, 'predicted_date' => $prediction['predicted_date']],
            ['predicted_quantity' => $prediction['predicted_quantity']]
        );
    }

    // CHANGED: Handle multiple predictions in success result
    private function successResult($code, $predictions)
    {
        return [
            'code' => $code,
            'ok' => true,
            'predictions' => array_map(function($p) {
                return [
                    'predicted_date' => $p->predicted_date,
                    'predicted_quantity' => (float) $p->predicted_quantity,
                ];
            }, $predictions)
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

    private function parseHarvestsJson($json): array
    {
        try {
            $data = is_array($json) ? $json : json_decode($json, true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
        $rows = [];
        foreach ($data as $row) {
            $date = $row['harvest_date'] ?? $row['date'] ?? null;
            $kg = $row['harvest_weight_kg'] ?? $row['weight'] ?? $row['kg'] ?? null;
            if (!$date || $kg === null) continue;
            $rows[] = [
                'harvest_date' => date('Y-m-d', strtotime($date)),
                'harvest_weight_kg' => (float) $kg,
            ];
        }
        return $rows;
    }
}