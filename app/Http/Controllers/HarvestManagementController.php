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
use Illuminate\Support\Facades\Storage;
use App\HarvestPrediction;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
        $sort = request('sort', 'code'); // code|dbh|height|records
        $dir = strtolower(request('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $yieldingOnly = (bool) request('yielding', false);
        $hasRecordsOnly = (bool) request('has_records', false);

        // Use stricter yielding thresholds per request (DBH ≥ 15–20 cm, Height ≥ 4–6 m)
        $minDbh = (float) request('min_dbh', config('services.harvest.min_dbh_cm', 15));
        $minHeight = (float) request('min_height', config('services.harvest.min_height_m', 4));

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

        // Always hide non-yielding from page per requirement
        $codes = $codes->where('is_yielding', true)->values();
        if ($hasRecordsOnly) {
            $codes = $codes->filter(fn($c) => $c->records_count > 0)->values();
        }

        // Sorting (PHP 7 compatible)
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

        // Recent harvests list for sidebar/table
        $harvests = Harvest::latest('harvest_date')->take(50)->get();

            $predictions = \App\HarvestPrediction::select('code', 'predicted_quantity', 'predicted_date')
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

        // Transform to FullCalendar format
        $calendarData = $predictions->mapWithKeys(function ($prediction) {
            return [
                $prediction->tree_code => [
                    'predicted_date' => \Carbon\Carbon::parse($prediction->predicted_date)->toDateString(), //  ensures "YYYY-MM-DD"
                    'predicted_quantity' => $prediction->predicted_quantity,
                ]
            ];
        });

        $files = glob(storage_path('app/predictions/*_prediction.json'));
        rsort($files); // sort newest first
        $path = $files[0] ?? null;

        if (!$path || !file_exists($path)) {
            return view('pages.harvest-management', [
                'forecast' => null,
                'evaluation' => null,
                'error' => 'No prediction file found. Please run the SARIMA script first.'
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
            'calendarData' => $grouped, //Pass predictions to Blade
            'calendarRaw' => $calendarData, //Pass predictions to Blade
            'yieldingOnly' => $yieldingOnly,
            'forecast' => $data['forecast'] ?? null,
            'evaluation' => $data['evaluation'] ?? null,
        ]);
    }


    public function store(HarvestStoreRequest $request)
    {
        $payload = $request->validated();

        // Normalize code casing based on existing tree_code record
        $tc = TreeCode::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($payload['code']))])->first();
        if ($tc) {
            $payload['code'] = $tc->code;
        }

        // Directory and file path (use relative path for Storage)
        $dir = 'harvest_data';
        $filename = "{$payload['code']}.csv";
        $path = "{$dir}/{$filename}";

        // Ensure directory exists
        Storage::disk('local')->makeDirectory($dir);

        // Read existing CSV data if available
        $existing = [];
        if (Storage::disk('local')->exists($path)) {
            $existing = collect(explode("\n", trim(Storage::disk('local')->get($path))))
                ->skip(1) // skip header
                ->filter()
                ->map(fn($line) => str_getcsv($line))
                ->map(fn($arr) => ['harvest_date' => $arr[0], 'harvest_weight_kg' => $arr[1]])
                ->toArray();
        }

        // Prevent duplicate entries for same date
        $alreadyExists = collect($existing)->contains(fn($row) => $row['harvest_date'] === $request->harvest_date);
        if ($alreadyExists) {
            return back()->with('error', 'A record for this date already exists.');
        }

        // Append new record
        $existing[] = [
            'harvest_date' => $request->harvest_date,
            'harvest_weight_kg' => $request->harvest_weight_kg,
        ];

        // Sort by date before saving back to CSV
        usort($existing, fn($a, $b) => strcmp($a['harvest_date'], $b['harvest_date']));

        // Rebuild CSV content
        $csvContent = "harvest_date,harvest_weight_kg\n";
        foreach ($existing as $row) {
            $csvContent .= "{$row['harvest_date']},{$row['harvest_weight_kg']}\n";
        }

        // Save CSV using Storage (relative path)
        Storage::disk('local')->put($path, $csvContent);

        // Save to DB
        Harvest::create($payload);

        return back()->with('success', 'Harvest record added successfully.');
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

    //evaluation metrics
//     protected function evaluateForecast(Collection $forecast, Collection $actuals)
//     {
//     // --- Month-by-month alignment ---
//     $results = $forecast->map(function ($f) use ($actuals) {
//     $predMonth = \Carbon\Carbon::parse($f['predicted_date'])->format('Y-m');
//     $actual = $actuals->first(function ($a) use ($predMonth) {
//         return \Carbon\Carbon::parse($a['harvest_date'])->format('Y-m') === $predMonth;
//     });

//     return [
//         'date'      => $f['predicted_date'],
//         'predicted' => $f['predicted_quantity'],
//         'actual'    => $actual['harvest_weight_kg'] ?? null,
//     ];
// })->map(function ($r) {
//     if ($r['actual'] === null) {
//         $r['actual'] = 0.0;
//     }
//     return $r;
// });

//     $harvestMonths = [1,2,3]; // or from config
//     $seasonResults = $results->filter(function ($r) use ($harvestMonths) {
//         $m = \Carbon\Carbon::parse($r['date'])->month;
//         return in_array($m, $harvestMonths);
//     });

//     $errors = $seasonResults->map(fn($r) => $r['predicted'] - $r['actual']);
//     $mae = $errors->map(fn($e) => abs($e))->avg();
//     $rmse = sqrt($errors->map(fn($e) => $e ** 2)->avg());
//     $mape = $seasonResults->map(function ($r) {
//         $den = (abs($r['predicted']) + abs($r['actual'])) ?: 1;
//         return abs($r['predicted'] - $r['actual']) / $den;
//     })->avg() * 100;


//     //date for evaluation
// $firstPred = $forecast->first();
// $year = \Carbon\Carbon::parse($firstPred['predicted_date'])->year;

// // Harvest months from config (default Jan–Mar)
// $monthsCsv = config('services.harvest.harvest_months', '1,2,3');
// $harvestMonths = array_values(array_filter(array_map('intval', explode(',', $monthsCsv))));
// sort($harvestMonths);

// $seasonStart = \Carbon\Carbon::create($year, min($harvestMonths), 1)->toDateString();
// $seasonEnd   = \Carbon\Carbon::create($year, max($harvestMonths), 1)->endOfMonth()->toDateString();

//     $seasonPredicted = $forecast->filter(function ($f) use ($seasonStart, $seasonEnd) {
//         $d = \Carbon\Carbon::parse($f['predicted_date']);
//         return $d->between($seasonStart, $seasonEnd);
//     })->sum('predicted_quantity');

//     $seasonActual = $actuals->filter(function ($a) use ($seasonStart, $seasonEnd) {
//         $d = \Carbon\Carbon::parse($a['harvest_date']);
//         return $d->between($seasonStart, $seasonEnd);
//     })->sum('harvest_weight_kg');

//     $seasonError = $seasonPredicted - $seasonActual;

//     return [
//         'mae' => $mae,
//         'rmse' => $rmse,
//         'mape' => $mape,
//         'results' => $results,
//         'season' => [
//             'start' => $seasonStart,
//             'end' => $seasonEnd,
//             'predicted_total' => $seasonPredicted,
//             'actual_total' => $seasonActual,
//             'error' => $seasonError,
//         ]
//     ];
// }

    protected function evaluateForecast(Collection $forecast, Collection $actuals)
    {
    // --- Step 1: Aggregate actuals by month ---
    $actualsByMonth = $actuals->groupBy(function ($a) {
        return \Carbon\Carbon::parse($a['harvest_date'])->format('Y-m');
    })->map(function ($rows) {
        return collect($rows)->sum('harvest_weight_kg');
    });

    // --- Step 2: Align forecast with actuals by month ---
    $results = $forecast->map(function ($f) use ($actualsByMonth) {
        $predMonth = \Carbon\Carbon::parse($f['predicted_date'])->format('Y-m');
        $actual = $actualsByMonth[$predMonth] ?? 0.0;

        return [
            'date'      => $f['predicted_date'],
            'predicted' => $f['predicted_quantity'],
            'actual'    => $actual,
            'error'     => $f['predicted_quantity'] - $actual,
        ];
    });

    // --- Step 3: Error metrics ---
    $errors = $results->map(fn($r) => $r['error']);
    $mae = $errors->map(fn($e) => abs($e))->avg();
    $rmse = sqrt($errors->map(fn($e) => $e ** 2)->avg());

    // MAPE: exclude zero-actual months
    $mape = $results->filter(fn($r) => $r['actual'] > 0)
        ->map(fn($r) => abs($r['predicted'] - $r['actual']) / $r['actual'])
        ->avg();
    $mape = $mape ? $mape * 100 : null;

    // sMAPE: handles zero-actual months gracefully
    $smape = $results->map(function ($r) {
        $den = (abs($r['predicted']) + abs($r['actual'])) ?: 1;
        return abs($r['predicted'] - $r['actual']) / $den;
    })->avg() * 100;

    // --- Step 4: Season totals (dynamic year) ---
    $year = \Carbon\Carbon::parse($forecast->first()['predicted_date'])->year;
    $harvestMonths = [1,2,3]; // or from config
    $seasonStart = \Carbon\Carbon::create($year, min($harvestMonths), 1)->toDateString();
    $seasonEnd   = \Carbon\Carbon::create($year, max($harvestMonths), 1)->endOfMonth()->toDateString();

    $seasonPredicted = $results->filter(function ($r) use ($seasonStart, $seasonEnd) {
        $d = \Carbon\Carbon::parse($r['date']);
        return $d->between($seasonStart, $seasonEnd);
    })->sum('predicted');

    $seasonActual = $results->filter(function ($r) use ($seasonStart, $seasonEnd) {
        $d = \Carbon\Carbon::parse($r['date']);
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
        // Example: load forecast JSON (from file, API, or DB)
        $forecast = collect(json_decode(file_get_contents(storage_path('app/predictions/SOUR1_prediction.json')), true)['forecast']['monthly_predictions']);

        // Example: load actuals from DB
        $actuals = \DB::table('harvests')
            ->select('harvest_date', 'harvest_weight_kg')
            ->get()
            ->map(fn($row) => (array) $row);

        $evaluation = $this->evaluateForecast($forecast, $actuals);

        return view('harvests.evaluate', compact('evaluation'));
    }

    // in DashboardController or HarvestsController
    public function upcoming(Request $request)
    {
        $query = HarvestPrediction::with(['treeCode.treeType'])
            ->pending();

        if ($request->filled('month')) {
            $query->whereMonth('predicted_date', $request->input('month'));
        }

        if ($request->filled('type')) {
            $map = ['sour'=>1,'sweet'=>2,'semi_sweet'=>3];
            $type = $request->input('type');
            if (isset($map[$type])) {
                $query->whereHas('treeCode', fn($q) => $q->where('tree_type_id', $map[$type]));
            } else {
                $query->whereHas('treeCode.treeType', fn($q) => $q->where('slug', $type)->orWhere('name', 'like', $type));
            }
        }

        $harvests = $query->orderBy('predicted_date')->paginate(15)->withQueryString();

        return view('harvests.upcoming', compact('harvests'));
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

            // Create Harvest record using the code string (no tree_code_id)
            $harvest = new Harvest();
            $harvest->code = $prediction->code; // use your existing code column
            $harvest->harvest_date = $prediction->predicted_date
                ? Carbon::parse($prediction->predicted_date)
                : now();
            // Adjust the column name below if your harvests table uses a different field
            $harvest->harvest_weight_kg = $data['actual_quantity'];
            $harvest->created_by = Auth::id();
            $harvest->save();

            // Update prediction status and attach metadata
            $prediction->status = HarvestPrediction::STATUS_DONE;
            $prediction->actual_quantity = $data['actual_quantity'];
            $prediction->harvest_id = $harvest->id; // optional if column exists
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
}