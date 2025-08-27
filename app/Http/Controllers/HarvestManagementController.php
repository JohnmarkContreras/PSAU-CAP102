<?php

namespace App\Http\Controllers;

use App\Tree;
use App\Harvest;
use App\HarvestPrediction;
use App\Imports\HarvestsImport;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HarvestManagementController extends Controller
{
    public function index()
    {
        $allTrees = Tree::orderBy('code')->get();
        $trees = Tree::with(['harvests', 'latestPrediction'])->orderBy('code')->paginate(10);
        $harvests = Harvest::with('tree')->latest('harvest_date')->paginate(10);
        return view ('pages.harvest-management', compact('allTrees', 'trees', 'harvests'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'         => 'required|exists:trees,code',
            'harvest_date'      => 'required|date',
            'harvest_weight_kg' => 'required|numeric|min:0',
            'quality'           => 'nullable|string|max:50',
            'notes'             => 'nullable|string',
        ]);

        Harvest::create($request->only('code','harvest_date','harvest_weight_kg','quality','notes'));
        return back()->with('success', 'Harvest record added.');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        Excel::import(new HarvestsImport, $request->file('file'));
        return back()->with('success', 'Excel data imported.');
    }

    public function predictAll()
    {
        $trees = Tree::all();
        $results = [];

        foreach ($trees as $tree) {
            try {
                $rows = $tree->harvests()
                    ->select('harvest_date','harvest_weight_kg')
                    ->orderBy('harvest_date')
                    ->get();

                if ($rows->count() < 6) {
                    $results[$tree->code] = [
                        'code' => $tree->code,
                        'ok' => false,
                        'message' => 'Need at least 6 records to forecast.'
                    ];
                    continue;
                }

                // Write CSV
                $csv = "harvest_date,harvest_weight_kg\n";
                foreach ($rows as $r) {
                    $csv .= "{$r->harvest_date},{$r->harvest_weight_kg}\n";
                }

                $path = "harvest_data/{$tree->code}.csv";
                Storage::disk('local')->put($path, $csv);
                $full = storage_path("app/$path");

                // Run Python script
                $script = base_path('scripts/sarima_predict.py');
                $python = env('PYTHON_BIN', 'python');

                $process = new Process([$python, $script, $full, '--order', '4,1,4', '--seasonal', '0,1,0,12']);
                $process->setTimeout(60);
                $process->run();

                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $out = json_decode(trim($process->getOutput()), true);

                if (!$out || !isset($out['predicted_quantity'], $out['predicted_date'])) {
                    $results[$tree->code] = [
                        'code' => $tree->code,
                        'ok' => false,
                        'message' => 'Invalid prediction output from Python.'
                    ];
                    continue;
                }

                // Save prediction in DB
                $pred = HarvestPrediction::updateOrCreate(
                    ['code' => $tree->code, 'predicted_date' => $out['predicted_date']],
                    ['predicted_quantity' => $out['predicted_quantity']]
                );

                $results[$tree->code] = [
                    'code' => $tree->code,
                    'ok' => true,
                    'predicted_date' => $pred->predicted_date,
                    'predicted_quantity' => (float) $pred->predicted_quantity
                ];

            } catch (\Throwable $e) {
                $results[$tree->code] = [
                    'code' => $tree->code,
                    'ok' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'results' => $results
        ]);
    }

}
