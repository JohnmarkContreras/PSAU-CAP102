<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TreeData;
use App\TreeCode;
class TreeDataController extends Controller
{
    
    // Show form to create new tree data entry
public function create(Request $request)
{
    $treeCodes = TreeCode::all();
    $defaultCode = $request->query('code');
    $defaultCodeId = null;
    if ($defaultCode) {
        $matched = TreeCode::where('code', $defaultCode)->first();
        if ($matched) $defaultCodeId = $matched->id;
    }
    if (!$defaultCodeId && $request->query('tree_code_id')) {
        $defaultCodeId = $request->query('tree_code_id');
    }
    return view('tree_data.create', compact('treeCodes', 'defaultCodeId'));
}
    // Store new tree data entry
    public function store(Request $request)
{
    $data = $request->validate([
        'tree_code_id' => 'nullable|exists:tree_code,id',
        'dbh' => 'required|numeric',      // inches
        'height' => 'required|numeric',   // meters
        'age' => 'nullable|integer',
        'stem_diameter' => 'nullable|numeric',
        'canopy_diameter' => 'nullable|numeric',
        // other fields...
    ]);

    // Prevent duplicates based on tree_code_id only
    $exists = TreeData::where('tree_code_id', $data['tree_code_id'])->first();

    if ($exists) {
        return redirect()->back()
            ->withErrors(['duplicate' => 'A record for this tree code already exists.'])
            ->withInput();
    }


    $row = TreeData::create($data);

    // optional: pass species-specific params if available, e.g. from TreeCode
    $params = [];
    if ($row->treeCode && isset($row->treeCode->alpha)) {
        $params['alpha'] = (float) $row->treeCode->alpha;
    }

    $row->computeAndSaveCarbon($params, true);

    return redirect()->route('trees-images.index')->with('success', 'Tree data added successfully!');
}

public function update(Request $request, TreeData $treeData)
{
    $data = $request->validate([
        'dbh' => 'required|numeric',
        'height' => 'required|numeric',
        'age' => 'nullable|integer',
        'stem_diameter' => 'nullable|numeric',
        'canopy_diameter' => 'nullable|numeric',
    ]);

    $treeData->update($data);

    // recompute with same logic as store
    $params = [];
    if ($treeData->treeCode && isset($treeData->treeCode->alpha)) {
        $params['alpha'] = (float) $treeData->treeCode->alpha;
    }

    $treeData->computeAndSaveCarbon($params, true);

    return redirect()->route('tree_data.show', $treeData->id);
}

    public function show(\App\TreeData $treeData)
    {
        $treeData->load('treeCode');
        return view('tree_data.show', compact('treeData'));
    }


/**
     * Compute and persist carbon metrics for a single TreeData row.
     * POST /tree_data/{treeData}/compute-carbon
     */
    public function computeCarbon(Request $request, TreeData $treeData)
    {
        $params = $request->only(['alpha', 'carbon_fraction', 'annual_growth_fraction']);

        // Ensure numeric params if provided
        $sanitized = [];
        if (isset($params['alpha'])) $sanitized['alpha'] = (float)$params['alpha'];
        if (isset($params['carbon_fraction'])) $sanitized['carbon_fraction'] = (float)$params['carbon_fraction'];
        if (isset($params['annual_growth_fraction'])) $sanitized['annual_growth_fraction'] = (float)$params['annual_growth_fraction'];

        $payload = $treeData->computeAndSaveCarbon($sanitized, true);

        return response()->json(['status' => 'ok', 'payload' => $payload], 200);
    }

    /**
     * Compute and persist carbon metrics for many TreeData rows in chunks.
     * POST /tree_data/compute-carbon/bulk
     * Optional request body or query: tree_code_id, alpha, carbon_fraction, annual_growth_fraction
     */
    public function computeCarbonBulk(Request $request)
    {
        $params = $request->only(['alpha', 'carbon_fraction', 'annual_growth_fraction']);
        $sanitized = [];
        if (isset($params['alpha'])) $sanitized['alpha'] = (float)$params['alpha'];
        if (isset($params['carbon_fraction'])) $sanitized['carbon_fraction'] = (float)$params['carbon_fraction'];
        if (isset($params['annual_growth_fraction'])) $sanitized['annual_growth_fraction'] = (float)$params['annual_growth_fraction'];

        $query = TreeData::query();

        if ($request->filled('tree_code_id')) {
            $query->where('tree_code_id', $request->input('tree_code_id'));
        }

        $updated = 0;
        $query->chunkById(200, function ($rows) use (&$updated, $sanitized) {
            foreach ($rows as $row) {
                $row->computeAndSaveCarbon($sanitized, true);
                $updated++;
            }
        });

        return response()->json(['status' => 'ok', 'updated' => $updated], 200);
    }

    /**
     * Optional: show the carbon UI (if you want controller to serve the carbon blade)
     * GET /tree_data/carbon
     */
    public function carbon(Request $request)
    {
        $trees = TreeData::with('treeCode')->orderBy('id')->get();
        return view('tree_data.carbon', compact('trees'));
    }

    /**
     * Optional: index of measurements that already have computed sequestration
     * GET /tree_data/sequestered
     */
    public function indexSequestered(Request $request)
    {
        $query = TreeData::with('treeCode')
            ->whereNotNull('annual_sequestration_kgco2')
            ->orderBy('tree_code_id')
            ->orderBy('id');

        if ($request->filled('tree_code_id')) {
            $query->where('tree_code_id', $request->input('tree_code_id'));
        }

        $rows = $query->paginate(50)->withQueryString();
        $treeCodes = \App\Models\TreeCode::orderBy('code')->get(['id', 'code']);

        return view('tree_data.sequestered', compact('rows', 'treeCodes'));
    }

    public function analyticsCarbon()
    {
        // only rows with computed sequestration
        $rows = \App\TreeData::with('treeCode')
            ->whereNotNull('annual_sequestration_kgco2')
            ->orderBy('tree_code_id')
            ->orderBy('id')
            ->get();

        // build chart data server-side for Blade
        $chartData = $rows->map(function ($r) {
            return [
                'id' => $r->id,
                'label' => optional($r->treeCode)->code ?? 'ID '.$r->id,
                'sequestration' => (float) ($r->annual_sequestration_kgco2 ?? 0),
            ];
        });

        return view('analytics.carbon', ['chartData' => $chartData]);
    }

public function getProjectionAnalytics(Request $request)
{
    $years = (int) $request->query('years', 10);
    $growthRate = 0.02; // 2% annual increase assumption

    // Fetch all trees that already have annual sequestration saved
    $trees = \App\TreeData::whereNotNull('annual_sequestration_kgco2')->get();

    $projectionData = [];
    $total = 0;

    foreach ($trees as $tree) {
        $baseSequestration = (float) $tree->annual_sequestration_kgco2;
        $projection = [];
        $current = $baseSequestration;

        for ($i = 1; $i <= $years; $i++) {
            // apply growth (e.g., 2% per year)
            $current = $current * (1 + $growthRate);
            $projection[] = [
                'year' => now()->year + $i,
                'sequestration' => round($current, 2),
            ];
        }

        $projectionData[] = [
            'tree_id' => $tree->id,
            'base' => $baseSequestration,
            'projection' => $projection,
        ];

        $total += $baseSequestration;
    }

    return response()->json([
        'years' => range(now()->year + 1, now()->year + $years),
        'total' => round($total, 2),
        'data' => $projectionData,
    ]);
}

}