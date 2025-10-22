<?php

namespace App\Http\Controllers;

use App\TreeCode;
use App\TreeData;
use App\Harvest;
use App\TreeType;
use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Http\Requests\HarvestStoreRequest;
use App\Http\Requests\HarvestImportRequest;
use App\Services\HarvestPredictionService;
use App\Services\HarvestImportService;
use App\Services\GeotagNotificationService;
use Illuminate\Support\Facades\Storage;
use App\HarvestPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $sort = request('sort', 'code');
        $dir = strtolower(request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $yieldingOnly = (bool) request('yielding', false);
        $hasRecordsOnly = (bool) request('has_records', false);

        $minDbh = (float) request('min_dbh', config('services.harvest.min_dbh_cm', 15));
        $minHeight = (float) request('min_height', config('services.harvest.min_height_m', 4));

        // OPTIMIZATION: Single query with eager loading
        $codes = TreeCode::with(['latestTreeData', 'latestPrediction'])
            ->when($q, fn($query) => $query->where('code', 'like', "%".trim($q)."%"))
            ->orderBy($sort, $dir)
            ->paginate(50);


        // OPTIMIZATION: Fetch ALL harvest counts in ONE query instead of N queries
        $harvestCounts = DB::table('harvests')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->pluck('count', 'code')
            ->toArray();

        // Map data without N+1 queries
        $codes = $codes->map(function ($tc) use ($minDbh, $minHeight, $harvestCounts) {
            $dbh = optional($tc->latestTreeData)->dbh;
            $height = optional($tc->latestTreeData)->height;
            $tc->computed_dbh = is_null($dbh) ? null : (float) $dbh;
            $tc->computed_height = is_null($height) ? null : (float) $height;
            $tc->is_yielding = $tc->computed_dbh !== null && $tc->computed_height !== null
                ? ($tc->computed_dbh >= $minDbh && $tc->computed_height >= $minHeight)
                : false;
            $tc->records_count = $harvestCounts[$tc->code] ?? 0;
            return $tc;
        });

        $codes = TreeCode::with(['harvests', 'latestPrediction'])->get();

        $groupedHarvests = $codes->mapWithKeys(function ($tc) {
            return [strtoupper(trim($tc->code)) => $tc->harvests];
        });
        

        if ($hasRecordsOnly) {
            $codes = $codes->filter(fn($c) => isset($groupedHarvests[$c->code]) && $groupedHarvests[$c->code]->isNotEmpty())->values();
        }


        if ($sort === 'dbh') {
            $codes = $codes->sortBy('computed_dbh', SORT_REGULAR, $dir === 'desc');
        } elseif ($sort === 'height') {
            $codes = $codes->sortBy('computed_height', SORT_REGULAR, $dir === 'desc');
        } elseif ($sort === 'records') {
            $codes = $codes->sortBy('records_count', SORT_REGULAR, $dir === 'desc');
        } else {
            $codes = $codes->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE, $dir === 'desc');
        }
        $codes = $codes->values();

        // Recent harvests list
        $harvests = Harvest::orderByRaw('CAST(STR_TO_DATE(TRIM(harvest_date), "%b %e, %Y") AS DATE) DESC')
            ->get();

        $predictions = HarvestPrediction::select('code', 'predicted_quantity', 'predicted_date')
            ->orderBy('predicted_date')
            ->get();

        $grouped = $predictions->groupBy('code')->map(function ($rows) {
            $latest = $rows->last();
            return [
                'ok' => true,
                'predicted_date' => $latest->predicted_date,
                'predicted_quantity' => $latest->predicted_quantity,
            ];
        });

        // Transform predictions to FullCalendar format
        $calendarData = $predictions->mapWithKeys(function ($prediction) {
            return [
                $prediction->code => [
                    'predicted_date' => Carbon::parse($prediction->predicted_date)->toDateString(),
                    'predicted_quantity' => $prediction->predicted_quantity,
                ]
            ];
        });

        // Get actual harvest records for calendar
        $actualHarvests = Harvest::select('code', 'harvest_date', 'harvest_weight_kg')
            ->whereNotNull('harvest_date')
            ->get();

        $codesWithHarvests = $actualHarvests->pluck('code')->unique()->toArray();

        // Build calendar events array
        $allCalendarEvents = [];

        // Add actual harvest events (GREEN)
        foreach ($actualHarvests as $harvest) {
            $allCalendarEvents[] = [
                'title' => "✓ {$harvest->code} ({$harvest->harvest_weight_kg} kg)",
                'start' => Carbon::parse($harvest->harvest_date)->toDateString(),
                'allDay' => true,
                'backgroundColor' => '#10b981',
                'borderColor' => '#059669',
                'textColor' => '#fff',
                'extendedProps' => [
                    'type' => 'actual',
                    'code' => $harvest->code,
                    'quantity' => $harvest->harvest_weight_kg,
                    'date' => Carbon::parse($harvest->harvest_date)->toDateString(),
                ]
            ];
        }

        // Add prediction events (BLUE) ONLY for trees that have actual harvests
        foreach ($predictions as $prediction) {
            if (in_array($prediction->code, $codesWithHarvests)) {
                $allCalendarEvents[] = [
                    'title' => "📅 {$prediction->code} ({$prediction->predicted_quantity} kg)",
                    'start' => Carbon::parse($prediction->predicted_date)->toDateString(),
                    'allDay' => true,
                    'backgroundColor' => '#38bdf8',
                    'borderColor' => '#0ea5e9',
                    'textColor' => '#fff',
                    'extendedProps' => [
                        'type' => 'predicted',
                        'code' => $prediction->code,
                        'quantity' => $prediction->predicted_quantity,
                        'date' => Carbon::parse($prediction->predicted_date)->toDateString(),
                    ]
                ];
            }
        }

        $files = glob(storage_path('app/predictions/*_prediction.json'));
        rsort($files);
        $path = $files[0] ?? null;

        if (!$path || !file_exists($path)) {
            return view('pages.harvest-management', [
                'codes' => collect(),
                'harvests' => collect(),
                'q' => $q,
                'sort' => $sort,
                'dir' => $dir,
                'minDbh' => $minDbh,
                'minHeight' => $minHeight,
                'calendarData' => collect(),
                'calendarRaw' => collect(),
                'allCalendarEvents' => [],
                'yieldingOnly' => $yieldingOnly,
                'forecast' => null,
                'evaluation' => null,
                'error' => 'No prediction file found.'
            ]);
        }

        $data = json_decode(file_get_contents($path), true);

        return view('pages.harvest-management', [
            'codes' => $codes,
            'harvests' => $harvests,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'minDbh' => $minDbh,
            'minHeight' => $minHeight,
            'calendarData' => $grouped,
            'calendarRaw' => $calendarData,
            'allCalendarEvents' => $allCalendarEvents,
            'yieldingOnly' => $yieldingOnly,
            'forecast' => $data['forecast'] ?? null,
            'evaluation' => $data['evaluation'] ?? null,
            'groupedHarvests' => $groupedHarvests,
        ]);
    }

    public function store(HarvestStoreRequest $request)
    {
        $payload = $request->validated();

        $code = mb_strtoupper(trim($payload['code']));
        $payload['code'] = $code;

        $dir = 'harvest_data';
        $filename = "{$code}.csv";
        $path = "{$dir}/{$filename}";

        Storage::disk('local')->makeDirectory($dir);

        // --- Read existing CSV records ---
        $existing = [];
        if (Storage::disk('local')->exists($path)) {
            $existing = collect(explode("\n", trim(Storage::disk('local')->get($path))))
                ->skip(1)
                ->filter()
                ->map(fn($line) => str_getcsv($line))
                ->map(fn($arr) => [
                    'harvest_date' => $arr[0],
                    'harvest_weight_kg' => $arr[1]
                ])
                ->toArray();
        }

        // --- Check duplicate ---
        $alreadyExists = collect($existing)->contains(fn($row) =>
            $row['harvest_date'] === $request->harvest_date
        );

        if ($alreadyExists) {
            return back()->with('error', 'A record for this date already exists.');
        }

        // --- Add new entry ---
        $existing[] = [
            'harvest_date' => $request->harvest_date,
            'harvest_weight_kg' => $request->harvest_weight_kg,
        ];

        usort($existing, fn($a, $b) => strcmp($a['harvest_date'], $b['harvest_date']));

        $csvContent = "harvest_date,harvest_weight_kg\n";
        foreach ($existing as $row) {
            $csvContent .= "{$row['harvest_date']},{$row['harvest_weight_kg']}\n";
        }
        Storage::disk('local')->put($path, $csvContent);

        // --- Start DB transaction ---
        DB::beginTransaction();
        try {
            $payload['created_by'] = auth()->id();
            // Create the harvest record
            $harvest = Harvest::create($payload);

            // Find nearest prediction (within ±7 days)
            $prediction = HarvestPrediction::where('code', $code)
                ->whereBetween('predicted_date', [
                    Carbon::parse($request->harvest_date)->subDays(7),
                    Carbon::parse($request->harvest_date)->addDays(7)
                ])
                ->first();

                if ($prediction) {
                    // Link actual data to existing prediction
                    $prediction->update([
                        'actual_quantity' => $request->harvest_weight_kg,
                        'status' => 'done',
                        'harvest_id' => $harvest->id,
                    ]);
                }
                // } else {
                //     // Count existing actual harvests for this tree
                //     $harvestCount = Harvest::where('code', $code)->count();

                //     //  Only create a prediction record if we have enough history (>= 6)
                //     if ($harvestCount >= 6) {
                //         HarvestPrediction::create([
                //             'code' => $code,
                //             'predicted_date' => $request->harvest_date,
                //             'predicted_quantity' => $request->harvest_weight_kg,
                //             'actual_quantity' => $request->harvest_weight_kg,
                //             'status' => 'done',
                //             'harvest_id' => $harvest->id,
                //         ]);
                //     }
            DB::commit();
            return back()->with('success', 'Harvest record added successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'An error occurred while saving harvest data.');
        }
    }



    public function import(HarvestImportRequest $request)
    {
        $this->importService->import($request->file('file'));
        return back()->with('success', 'Excel data imported.');
    }

    public function predictAll()
    {
        $yieldingOnly = (bool) request('yielding', false);
        $results = $this->predictionService->predictAllTrees($yieldingOnly);
        return response()->json([
            'ok' => true,
            'results' => $results
        ]);
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

    protected function evaluateForecast(Collection $forecast, Collection $actuals)
    {
        $actualsByMonth = $actuals->groupBy(function ($a) {
            return Carbon::parse($a['harvest_date'])->format('Y-m');
        })->map(function ($rows) {
            return collect($rows)->sum('harvest_weight_kg');
        });

        $results = $forecast->map(function ($f) use ($actualsByMonth) {
            $predMonth = Carbon::parse($f['predicted_date'])->format('Y-m');
            $actual = $actualsByMonth[$predMonth] ?? 0.0;

            return [
                'date'      => $f['predicted_date'],
                'predicted' => $f['predicted_quantity'],
                'actual'    => $actual,
                'error'     => $f['predicted_quantity'] - $actual,
            ];
        });

        $errors = $results->map(fn($r) => $r['error']);
        $mae = $errors->map(fn($e) => abs($e))->avg();
        $rmse = sqrt($errors->map(fn($e) => $e ** 2)->avg());

        $mape = $results->filter(fn($r) => $r['actual'] > 0)
            ->map(fn($r) => abs($r['predicted'] - $r['actual']) / $r['actual'])
            ->avg();
        $mape = $mape ? $mape * 100 : null;

        $smape = $results->map(function ($r) {
            $den = (abs($r['predicted']) + abs($r['actual'])) ?: 1;
            return abs($r['predicted'] - $r['actual']) / $den;
        })->avg() * 100;

        $year = Carbon::parse($forecast->first()['predicted_date'])->year;
        $harvestMonths = [1,2,3];
        $seasonStart = Carbon::create($year, min($harvestMonths), 1)->toDateString();
        $seasonEnd   = Carbon::create($year, max($harvestMonths), 1)->endOfMonth()->toDateString();

        $seasonPredicted = $results->filter(function ($r) use ($seasonStart, $seasonEnd) {
            $d = Carbon::parse($r['date']);
            return $d->between($seasonStart, $seasonEnd);
        })->sum('predicted');

        $seasonActual = $results->filter(function ($r) use ($seasonStart, $seasonEnd) {
            $d = Carbon::parse($r['date']);
            return $d->between($seasonStart, $seasonEnd);
        })->sum('actual');

        return [
            'mae' => $mae,
            'rmse' => $rmse,
            'mape' => $mape,
            'smape' => $smape,
            'results' => $results,
            'season' => [
                'start' => $seasonStart,
                'end' => $seasonEnd,
                'predicted_total' => $seasonPredicted,
                'actual_total' => $seasonActual,
                'error' => $seasonPredicted - $seasonActual,
            ]
        ];
    }

    public function evaluate()
    {
        $forecast = collect(json_decode(file_get_contents(storage_path('app/predictions/SOUR1_prediction.json')), true)['forecast']['monthly_predictions']);
        $actuals = DB::table('harvests')
            ->select('harvest_date', 'harvest_weight_kg')
            ->limit(1000)
            ->get()
            ->map(fn($row) => (array) $row);

        $evaluation = $this->evaluateForecast($forecast, $actuals);

        return view('harvests.evaluate', compact('evaluation'));
    }

    public function upcoming(Request $request)
    {
        // Reload fresh predictions (pending only) with eager loading
        $query = HarvestPrediction::with(['treeCode.treeType'])
            ->where('status', HarvestPrediction::STATUS_PENDING);

        if ($request->filled('month')) {
            $query->whereMonth('predicted_date', $request->input('month'));
        }

        if ($request->filled('type')) {
            $map = ['sour'=>1, 'sweet'=>2, 'semi_sweet'=>3];
            $type = $request->input('type');
            if (isset($map[$type])) {
                $query->whereHas('treeCode', fn($q) => $q->where('tree_type_id', $map[$type]));
            } else {
                $query->whereHas('treeCode.treeType', fn($q) =>
                    $q->where('slug', $type)->orWhere('name', 'like', $type)
                );
            }
        }

        $harvests = $query->orderBy('predicted_date', 'asc')->paginate(15)->withQueryString();

        // OPTIMIZATION: Fetch harvest counts once
        $harvestCounts = DB::table('harvests')
            ->select('code', DB::raw('COUNT(*) as count'))
            ->groupBy('code')
            ->pluck('count', 'code')
            ->toArray();

        $q = request('q');
        $sort = request('sort', 'code');
        $dir = strtolower(request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $minDbh = (float) request('min_dbh', config('services.harvest.min_dbh_cm', 15));
        $minHeight = (float) request('min_height', config('services.harvest.min_height_m', 4));

        $codes = TreeCode::with(['latestTreeData', 'latestPrediction'])
            ->when($q, fn($query) => $query->where('code', 'like', "%".trim($q)."%"))
            ->orderBy($sort, $dir)
            ->paginate(50);


        // Use pre-fetched counts
        $codes = $codes->map(function ($tc) use ($minDbh, $minHeight, $harvestCounts) {
            $dbh = optional($tc->latestTreeData)->dbh;
            $height = optional($tc->latestTreeData)->height;
            $tc->computed_dbh = $dbh ? (float)$dbh : null;
            $tc->computed_height = $height ? (float)$height : null;
            $tc->is_yielding = $tc->computed_dbh && $tc->computed_height
                ? ($tc->computed_dbh >= $minDbh && $tc->computed_height >= $minHeight)
                : false;
            $tc->records_count = $harvestCounts[$tc->code] ?? 0;
            return $tc;
        })->filter(fn($c) => $c->is_yielding || $c->records_count > 0)
          ->sortBy($sort === 'dbh' ? 'computed_dbh' :
                   ($sort === 'height' ? 'computed_height' :
                   ($sort === 'records' ? 'records_count' : 'code')),
                   SORT_REGULAR, $dir === 'desc')
          ->values();

        $scriptPath = base_path('scripts/run_sarima.py');
        if (file_exists($scriptPath)) {
            exec("python3 $scriptPath > /dev/null 2>&1 &");
        }

        return view('harvests.upcoming', compact('harvests', 'codes'));
    }

    public function markDone(Request $request)
    {
        $data = $request->validate([
            'prediction_id'   => 'required|integer|exists:harvest_predictions,id',
            'actual_quantity' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $prediction = HarvestPrediction::lockForUpdate()->findOrFail($data['prediction_id']);

            if ($prediction->status === HarvestPrediction::STATUS_DONE) {
                return response()->json(['message' => 'Prediction already marked done.'], 422);
            }

            $harvest = new Harvest();
            $harvest->code = $prediction->code;
            $harvest->harvest_date = $prediction->predicted_date ? Carbon::parse($prediction->predicted_date) : now();
            $harvest->harvest_weight_kg = $data['actual_quantity'];
            $harvest->created_by = Auth::id();
            $harvest->save();

            $prediction->status = HarvestPrediction::STATUS_DONE;
            $prediction->actual_quantity = $data['actual_quantity'];
            $prediction->harvest_id = $harvest->id;
            $prediction->save();

            DB::commit();

            return response()->json([
                'message' => 'Harvest recorded and prediction marked done.',
                'remove_row' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function accuracy()
    {
        // Load predictions and related harvests
        $predictions = HarvestPrediction::with(['treeCode.harvests'])
            ->whereHas('treeCode.harvests')
            ->orderBy('predicted_date')
            ->get();

        $labels = [];
        $actual = [];
        $predicted = [];

        foreach ($predictions as $p) {
            $predictedMonth = Carbon::parse($p->predicted_date)->format('Y-m');

            // ✅ Compute total actual harvest within the same month
            $monthlyTotal = optional($p->treeCode->harvests)
                ->filter(function ($h) use ($predictedMonth) {
                    return Carbon::parse($h->harvest_date)->format('Y-m') === $predictedMonth;
                })
                ->sum('harvest_weight_kg');

            // Add to datasets
            $labels[] = Carbon::parse($p->predicted_date)->format('M Y');
            $predicted[] = (float) $p->predicted_quantity;
            $actual[] = (float) $monthlyTotal;
        }

        // Compute metrics only on valid pairs (non-zero actual)
        $validPairs = collect($actual)
            ->zip($predicted)
            ->filter(fn($pair) => $pair[0] > 0);

        $MAE = round($validPairs->avg(fn($p) => abs($p[0] - $p[1])), 2);
        $MSE = round($validPairs->avg(fn($p) => pow($p[0] - $p[1], 2)), 4);
        $RMSE = round(sqrt($validPairs->avg(fn($p) => pow($p[0] - $p[1], 2))), 2);
        $MAPE = round($validPairs->avg(fn($p) => $p[0] != 0
            ? abs(($p[0] - $p[1]) / $p[0]) * 100
            : 0), 2) . '%';

        $metrics = compact('MAE', 'MSE', 'RMSE', 'MAPE');

        return view('harvests.accuracy', compact('labels', 'actual', 'predicted', 'metrics'));
    }

public function backtest(Request $request)
{
    $code = $request->input('code');

    // Get distinct codes with records
    $codes = Harvest::select('code')
        ->distinct()
        ->orderBy('code')
        ->pluck('code');

    $query = Harvest::select('code','harvest_date','harvest_weight_kg');
    if ($code) {
        $query->where('code', $code);
    }
    $harvests = $query->orderBy('harvest_date')->get()->toArray();

    // Run Python process
    $process = new Process([
        base_path('venv/bin/python3'),
        base_path('scripts/backtest_sarima.py')
    ]);
    $process->setInput(json_encode($harvests));
    $process->run();

    if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
    }

    $data = json_decode($process->getOutput(), true);

    if (isset($data['error'])) {
        return view('harvests.backtest', [
            'error' => $data['error'],
            'mape' => null,
            'rmse' => null,
            'dates' => [],
            'actual' => [],
            'predicted' => [],
            'selectedCode' => $code,
            'codes' => $codes,
        ]);
    }

    return view('harvests.backtest', [
        'mape' => $data['mape'] ?? null,
        'rmse' => $data['rmse'] ?? null,
        'dates' => $data['dates'] ?? [],
        'actual' => $data['actual'] ?? [],
        'predicted' => $data['predicted'] ?? [],
        'selectedCode' => $code,
        'codes' => $codes,
    ]);
}

}