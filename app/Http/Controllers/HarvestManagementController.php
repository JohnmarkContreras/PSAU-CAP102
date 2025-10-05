<?php

namespace App\Http\Controllers;

use App\TreeCode;
use App\TreeData;
use App\Harvest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Http\Requests\HarvestStoreRequest;
use App\Http\Requests\HarvestImportRequest;
use App\Services\HarvestPredictionService;
use App\Services\HarvestImportService;
use App\Services\GeotagNotificationService;

class HarvestManagementController extends Controller
{
    private $predictionService;
    private $importService;

    public function __construct(
        HarvestPredictionService $predictionService,
        HarvestImportService $importService
    ) {
        $this->predictionService = $predictionService;
        $this->importService = $importService;
    }

    public function index()
    {
        $q = request('q');
        $sort = request('sort', 'code'); // code|dbh|height|records
        $dir = strtolower(request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $yieldingOnly = (bool) request('yielding', false);
        $hasRecordsOnly = (bool) request('has_records', false);

        $minDbh = (float) config('services.harvest.min_dbh_cm', 10);
        $minHeight = (float) config('services.harvest.min_height_m', 2);

        // Load codes with latest measurement and prediction
        $codes = TreeCode::with(['latestTreeData', 'latestPrediction'])
            ->when($q, function ($query) use ($q) {
                $query->where('code', 'like', "%".trim($q)."%");
            })
            ->orderBy('code')
            ->get();

        // Decorate with computed props for filters/sorts
        $recordsForCode = function (string $code) {
            $countDb = Harvest::where('code', $code)->count();
            // include JSON records from latest TreeData if present
            $td = TreeData::whereHas('treeCode', function ($q) use ($code) { $q->where('code', $code); })
                ->latest('id')->first();
            $countJson = 0;
            if ($td && !empty($td->harvests)) {
                $parsed = $this->parseHarvestsJson($td->harvests);
                $countJson = count($parsed);
            }
            return $countDb + $countJson;
        };

        $codes = $codes->map(function ($tc) use ($minDbh, $minHeight, $recordsForCode) {
            $dbh = optional($tc->latestTreeData)->dbh;
            $height = optional($tc->latestTreeData)->height;
            $tc->computed_dbh = is_null($dbh) ? null : (float) $dbh;
            $tc->computed_height = is_null($height) ? null : (float) $height;
            $tc->is_yielding = $tc->computed_dbh !== null && $tc->computed_height !== null
                ? ($tc->computed_dbh >= $minDbh && $tc->computed_height >= $minHeight)
                : false;
            $tc->records_count = $recordsForCode($tc->code);
            return $tc;
        });

        if ($yieldingOnly) {
            $codes = $codes->where('is_yielding', true)->values();
        }
        if ($hasRecordsOnly) {
            $codes = $codes->filter(fn($c) => $c->records_count > 0)->values();
        }

        // Sorting
        $codes = match ($sort) {
            'dbh' => $codes->sortBy('computed_dbh', SORT_REGULAR, $dir === 'desc'),
            'height' => $codes->sortBy('computed_height', SORT_REGULAR, $dir === 'desc'),
            'records' => $codes->sortBy('records_count', SORT_REGULAR, $dir === 'desc'),
            default => $codes->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE, $dir === 'desc'),
        }->values();

        // Recent harvests list for sidebar/table
        $harvests = Harvest::latest('harvest_date')->take(50)->get();

        return view('pages.harvest-management', [
            'codes' => $codes,
            'harvests' => $harvests,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'minDbh' => $minDbh,
            'minHeight' => $minHeight,
        ]);
    }

    public function store(HarvestStoreRequest $request)
    {
        Harvest::create($request->validated());
        return back()->with('success', 'Harvest record added.');
    }

    public function import(HarvestImportRequest $request)
    {
        $this->importService->import($request->file('file'));
        
        return back()->with('success', 'Excel data imported.');
    }

    public function predictAll()
    {
        $results = $this->predictionService->predictAllTrees();
        
        return response()->json([
            'ok' => true,
            'results' => $results
        ]);
    }

    /**
     * Parse flexible JSON stored in tree_data.harvests into an array of
     * ['harvest_date' => Y-m-d, 'harvest_weight_kg' => float]
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