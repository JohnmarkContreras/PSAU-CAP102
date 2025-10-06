<?php

namespace App\Services;

use App\TreeCode;
use App\Harvest;
use App\HarvestPrediction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Notifications\HarvestPredictionNotification;

class HarvestPredictionService
{
    private const MIN_RECORDS_REQUIRED = 6;
    private const MIN_DBH_CM = 10.0;   // configurable thresholds
    private const MIN_HEIGHT_M = 2.0;
    private const TIMEOUT_SECONDS = 60;

    public function predictAllTrees(bool $yieldingOnly = false)
    {
        $codes = TreeCode::orderBy('code')->get();
        $results = [];

        foreach ($codes as $tc) {
            $code = $tc->code;
            // Skip trees based on requested policy
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

        return $results;
    }

    private function predictForCode(string $code)
    {
        try {
            $harvests = $this->getCombinedHarvests($code);

            // Require at least 6 total points OR 6 distinct months OR 6 distinct years
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
                // Fallback: estimate from DBH & Height when history is insufficient
                $estimate = $this->estimateYieldFromMorphologyByCode($code);
                if ($estimate) {
                    $saved = $this->savePrediction($code, $estimate);
                    return $this->successResult($code, $saved);
                }
                return $this->errorResult($code, 'Need ≥6 records (points/months/years) to forecast.');
            }

            $csvPath = $this->generateCsvFileForCode($code, $harvests);
            $prediction = $this->runPredictionScript($csvPath);

            if (!$this->isValidPrediction($prediction)) {
                return $this->errorResult($code, 'Invalid prediction output from Python.');
            }

            $savedPrediction = $this->savePrediction($code, $prediction);

            return $this->successResult($code, $savedPrediction);

        } catch (\Throwable $e) {
            return $this->errorResult($code, $e->getMessage());
        }
    }

    /**
     * Fallback estimator if harvest history is insufficient.
     * Uses the latest TreeData (dbh cm, height m) to estimate next harvest quantity and date.
     */
    private function estimateYieldFromMorphologyByCode(string $code): ?array
    {
        $treeData = \App\TreeData::whereHas('treeCode', function ($q) use ($code) {
            $q->where('code', $code);
        })->latest('id')->first();

        if (! $treeData || $treeData->dbh === null || $treeData->height === null) {
            return null;
        }

        $dbh = max(0.0, (float) $treeData->dbh);     // cm
        $height = max(0.0, (float) $treeData->height); // m

        // Simple heuristic: yield (kg) ~ k * dbh^2 * height (scaled)
        // k derived as a small scaling factor to keep values in realistic range
        $k = 0.0006; // tune as data becomes available
        $predictedQty = round($k * $dbh * $dbh * $height, 2);

        // Choose upcoming season start month from config
        $monthsCsv = config('services.harvest.harvest_months', '12,1,2,3');
        $harvestMonths = array_values(array_filter(array_map('intval', explode(',', $monthsCsv))));
        if (empty($harvestMonths)) {
            $harvestMonths = [12,1,2,3];
        }
        sort($harvestMonths);

        $today = now();
        $year = (int) $today->year;
        // Find next upcoming harvest month
        $targetMonth = null;
        foreach ($harvestMonths as $m) {
            $candidate = now()->setDate($year, $m, 1);
            if ($candidate->isFuture()) { $targetMonth = $m; break; }
        }
        if ($targetMonth === null) {
            // All this season months passed; pick first month next year
            $targetMonth = $harvestMonths[0];
            $year += 1;
        }
        $predictedDate = now()->setDate($year, $targetMonth, 15)->toDateString(); // mid-month

        return [
            'predicted_quantity' => $predictedQty,
            'predicted_date' => $predictedDate,
        ];
    }

    private function getCombinedHarvests(string $code): array
    {
        // DB harvests (match common Sl/SI code prefix variants case-insensitively)
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

        // JSON harvests from latest TreeData
        $td = \App\TreeData::whereHas('treeCode', function ($q) use ($code) { $q->where('code', $code); })
            ->latest('id')->first();
        if ($td && !empty($td->harvests)) {
            $rows = array_merge($rows, $this->parseHarvestsJson($td->harvests));
        }

        // Sort by date and return raw rows (Python groups by month)
        usort($rows, function ($a, $b) {
            return strcmp($a['harvest_date'], $b['harvest_date']);
        });
        return $rows;
    }

    private function codeVariants(string $code): array
    {
        $code = trim($code);
        $low = mb_strtolower($code);
        $pfx = mb_substr($low, 0, 2);
        $rest = mb_substr($code, 2);
        $variants = [$code];
        // canonicalize to 'Sl' prefix
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
        // Try TreeData latest
        $treeData = \App\TreeData::whereHas('treeCode', function ($q) use ($code) {
            $q->where('code', $code);
        })->latest('id')->first();

        $dbhCm = null;
        $heightM = null;

        if ($treeData) {
            // Source may be inches/feet; normalize to metric if needed
            $dbhCm = is_null($treeData->dbh) ? null : (float) $treeData->dbh; // assume cm already based on model
            $heightM = is_null($treeData->height) ? null : (float) $treeData->height; // assume meters
        }

        if ($dbhCm === null || $heightM === null) {
            // If we do not have measurements, allow training but flag in result message later
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

    private function runPredictionScript($csvPath)
    {
        $script = base_path('scripts/sarima_predict.py');
        $python = env('PYTHON_BIN', 'python');
        $order = config('services.harvest.sarima_order', '4,1,4');
        $seasonal = config('services.harvest.sarima_seasonal', '0,1,0,12');
        $months = config('services.harvest.harvest_months', '12,1,2,3');

        $process = new Process([
            $python, $script, $csvPath, 
            '--order', $order, 
            '--seasonal', $seasonal,
            '--harvest_months', $months,
            '--mode', 'annual',
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
        $prediction = HarvestPrediction::updateOrCreate(
            ['code' => $treeCode, 'predicted_date' => $prediction['predicted_date']],
            ['predicted_quantity' => $prediction['predicted_quantity']]
        );
    // Get the user associated with this prediction
    $user = auth()->user();

    if ($user) {
        // Send Laravel Notification (email + SMS + database)
        $user->notify(new HarvestPredictionNotification($harvestPrediction));
    }

    // Just return the model (the controller can handle redirect or response)
    return $harvestPrediction;
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

    /**
     * Parse flexible JSON stored in tree_data.harvests into normalized rows
     * of ['harvest_date' => Y-m-d, 'harvest_weight_kg' => float]
     */
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