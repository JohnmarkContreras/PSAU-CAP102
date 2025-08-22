<?php

namespace App\Http\Controllers;

use App\Tree;
use App\Harvest;
use App\HarvestPrediction;
use App\Imports\HarvestsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class HarvestManagementController extends Controller
{
    public function index()
    {
        $trees = Tree::with(['harvests', 'latestPrediction'])->orderBy('code')->paginate(10);
        $harvests = Harvest::with('tree')->latest('harvest_date')->paginate(10);
        return view ('pages.harvest-management', compact('trees', 'harvests'));
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

    public function predict($treeCode)
    {
        $tree = Tree::where('code', $treeCode)->firstOrFail();
        $rows = $tree->harvests()->select('harvest_date','harvest_weight_kg')->orderBy('harvest_date')->get();

        if ($rows->count() < 6) {
            return response()->json(['ok' => false, 'message' => 'Need at least 6 records to forecast.'], 422);
        }

        // Write CSV for Python
        $csv = "harvest_date,harvest_weight_kg\n";
        foreach ($rows as $r) {
            $csv .= "{$r->harvest_date},{$r->harvest_weight_kg}\n";
        }

        $path = "harvest_data/{$treeCode}.csv";
        Storage::disk('local')->put($path, $csv);
        $full = storage_path("app/$path");

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
            return response()->json(['ok' => false, 'message' => 'Invalid prediction output.'], 500);
        }

        // $pred = HarvestPrediction::updateOrCreate(
        //     ['code' => $treeCode, 'predicted_date' => $out['predicted_date']],
        //     ['predicted_quantity' => $out['predicted_quantity']]
        // );

        // $users = User::all(); // Or filter by role
        // foreach ($users as $user) {
        //     $user->notify(new HarvestScheduleNotification($pred));
        // }

        return response()->json([
            'ok' => true,
            'code' => $treeCode,
            'predicted_date' => $pred->predicted_date,
            'predicted_quantity' => (float)$pred->predicted_quantity,
        ]);
    }
}
