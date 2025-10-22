<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TreeData;
use App\TreeCode;
use App\Harvest;
use App\TreeType;
use App\User;
class TreeDataController extends Controller
{
    
    // Show form to create new tree data entry
// public function create(Request $request)
// {
//     $treeCodes = TreeCode::all();
//     $defaultCode = $request->query('code');
//     $defaultCodeId = null;
//     if ($defaultCode) {
//         $matched = TreeCode::where('code', $defaultCode)->first();
//         if ($matched) $defaultCodeId = $matched->id;
//     }
//     if (!$defaultCodeId && $request->query('tree_code_id')) {
//         $defaultCodeId = $request->query('tree_code_id');
//     }
//     return view('tree_data.create', compact('treeCodes', 'defaultCodeId'));
// }
//     // Store new tree data entry
//     public function store(Request $request)
//     {
//         $data = $request->validate([
//             'tree_code_id' => 'nullable|exists:tree_code,id',
//             'dbh' => 'required|numeric',      // cm
//             'height' => 'required|numeric',   // meters
//             'age' => 'nullable|integer',
//             'stem_diameter' => 'nullable|numeric',
//             'canopy_diameter' => 'nullable|numeric',
//             // other fields...
//         ]);

//         //Prevent duplicates based on tree_code_id
//         $exists = \App\TreeData::where('tree_code_id', $data['tree_code_id'])->exists();

//         if ($exists) {
//             return redirect()->back()
//                 ->with('error', 'A tree data record for this code already exists. Please choose a different tree code.')
//                 ->withInput();
//         }

//         try {
//             $treeData = \App\TreeData::create($data);

//             // Compute carbon sequestration if applicable
//             $params = [];
//             if ($treeData->treeCode && isset($treeData->treeCode->alpha)) {
//                 $params['alpha'] = (float) $treeData->treeCode->alpha;
//             }

//             $treeData->computeAndSaveCarbon($params, true);

//             return redirect()
//                 ->route('trees-images.index')
//                 ->with('success', 'Tree data added successfully! Carbon sequestration computed.');
//         } catch (\Exception $e) {
//             \Log::error('Error creating tree data: ' . $e->getMessage(), [
//                 'file' => $e->getFile(),
//                 'line' => $e->getLine(),
//             ]);

//             return redirect()
//                 ->back()
//                 ->with('error', 'An unexpected error occurred while saving the tree data. Please try again.')
//                 ->withInput();
//         }
//     }

// // TreeDataController.php
// public function edit($tree_code_id)
//     {
//         // Optional: Restrict to superadmin
//         // $this->authorize('isSuperAdmin');
        
//         $tree = TreeData::where('tree_code_id', $tree_code_id)->firstOrFail();
//         return view('tree_data.edit', compact('tree'));
//     }

//     /**
//      * Update tree data including coordinates
//      */
//     public function update(Request $request, $tree_code_id)
//     {
//         // Optional: Restrict to superadmin
//         // $this->authorize('isSuperAdmin');
        
//         $tree = TreeData::where('tree_code_id', $tree_code_id)->firstOrFail();

//         $validated = $request->validate([
//             'tree_code_id'    => 'required|exists:tree_code,id|unique:tree_data,tree_code_id,' . $tree->id,
//             'dbh'             => 'required|numeric|min:0',
//             'height'          => 'required|numeric|min:0',
//             'age'             => 'nullable|integer|min:0',
//             'stem_diameter'   => 'nullable|numeric|min:0',
//             'canopy_diameter' => 'nullable|numeric|min:0',
//         ]);

//         // Update the tree with validated data (including new coordinates)
//         $tree->update($validated);

//         // Recompute carbon metrics
//         $params = $request->only(['alpha', 'carbon_fraction', 'annual_growth_fraction']);
//         $sanitized = [];
//         if (isset($params['alpha'])) $sanitized['alpha'] = (float)$params['alpha'];
//         if (isset($params['carbon_fraction'])) $sanitized['carbon_fraction'] = (float)$params['carbon_fraction'];
//         if (isset($params['annual_growth_fraction'])) $sanitized['annual_growth_fraction'] = (float)$params['annual_growth_fraction'];

//         $tree->computeAndSaveCarbon($sanitized, true);

//         return redirect()
//             ->route('tree_data.edit', $tree->tree_code_id)
//             ->with('success', 'Tree data, coordinates, and carbon metrics updated successfully!');
//     }

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
    try {
        $data = $request->validate([
            'tree_code_id' => 'nullable|exists:tree_code,id',
            'dbh' => 'required|numeric',      // cm
            'height' => 'required|numeric',   // meters
            'age' => 'nullable|integer',
            'stem_diameter' => 'nullable|numeric',
            'canopy_diameter' => 'nullable|numeric',
        ]);

        // Prevent duplicates based on tree_code_id
        if ($data['tree_code_id']) {
            $exists = \App\TreeData::where('tree_code_id', $data['tree_code_id'])->exists();

            if ($exists) {
                $message = 'A tree data record for this code already exists. Please choose a different tree code.';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', $message)
                    ->withInput();
            }
        }

        $treeData = \App\TreeData::create($data);

        // Compute carbon sequestration if applicable
        $params = [];
        if ($treeData->treeCode && isset($treeData->treeCode->alpha)) {
            $params['alpha'] = (float) $treeData->treeCode->alpha;
        }

        $treeData->computeAndSaveCarbon($params, true);

        $message = 'Tree data added successfully! Carbon sequestration computed.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('tree-images.index')
            ]);
        }

        return redirect()
            ->route('tree-images.index')
            ->with('success', $message);

    } catch (\Illuminate\Validation\ValidationException $e) {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        return redirect()
            ->back()
            ->withErrors($e->errors())
            ->withInput();

    } catch (\Exception $e) {
        \Log::error('Error creating tree data: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $message = 'An unexpected error occurred while saving the tree data. Please try again.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 500);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->withInput();
    }
}

    // Show edit form
    public function edit($id)
    {
        // Find by ID directly instead of relying on implicit binding
        $tree = \App\TreeData::with('treeCode')->findOrFail($id);
        return view('tree_data.edit', compact('tree'));
    }
public function update(Request $request, $id)
{
    // Find the tree record by ID
    $tree = \App\TreeData::with('treeCode')->findOrFail($id);

    try {
        $data = $request->validate([
            'tree_code_id' => 'required|exists:tree_code,id',
            'dbh' => 'required|numeric',
            'height' => 'required|numeric',
            'age' => 'nullable|integer',
            'stem_diameter' => 'nullable|numeric',
            'canopy_diameter' => 'nullable|numeric',
        ]);

        // Check for duplicates only if tree_code_id changed
        if ($tree->tree_code_id != $data['tree_code_id']) {
            $exists = \App\TreeData::where('tree_code_id', $data['tree_code_id'])->exists();
            
            if ($exists) {
                $message = 'A tree data record for this code already exists. Please choose a different tree code.';
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', $message)
                    ->withInput();
            }
        }

        $tree->update($data);

        // Recompute carbon sequestration
        $params = [];
        if ($tree->treeCode && isset($tree->treeCode->alpha)) {
            $params['alpha'] = (float) $tree->treeCode->alpha;
        }

        $tree->computeAndSaveCarbon($params, true);

        $message = 'Tree data updated successfully!';

        // Return JSON for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('tree-images.index')
            ]);
        }

        // Redirect back to map for regular requests
        return redirect()
            ->route('tree-images.index')
            ->with('success', $message);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // Handle validation errors
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        return redirect()
            ->back()
            ->withErrors($e->errors())
            ->withInput();

    } catch (\Exception $e) {
        \Log::error('Error updating tree data: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $message = 'An unexpected error occurred while updating the tree data. Please try again.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 500);
        }

        return redirect()
            ->back()
            ->with('error', $message)
            ->withInput();
    }
}
    /**
     * Show a specific tree with details
     */
    public function show($tree_code_id)
    {
        $tree = TreeData::where('tree_code_id', $tree_code_id)->firstOrFail();
        return view('tree_images.show', compact('tree'));
    }


// public function update(Request $request, TreeData $treeData)
// {
//     $data = $request->validate([
//         'dbh' => 'required|numeric',
//         'height' => 'required|numeric',
//         'age' => 'nullable|integer',
//         'stem_diameter' => 'nullable|numeric',
//         'canopy_diameter' => 'nullable|numeric',
//     ]);

//     $treeData->update($data);

//     // recompute with same logic as store
//     $params = [];
//     if ($treeData->treeCode && isset($treeData->treeCode->alpha)) {
//         $params['alpha'] = (float) $treeData->treeCode->alpha;
//     }

//     $treeData->computeAndSaveCarbon($params, true);

//     return redirect()->route('tree_data.show', $treeData->id);
// }

        // public function show($tree_code_id)
        // {
        //     $tree = TreeData::where('tree_code_id', $tree_code_id)->firstOrFail();
        //     return view('tree_images.show', compact('tree'));
        // }


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

        $rows = $query->get()->withQueryString();
        $treeCodes = \App\TreeCode::orderBy('code')->get(['id', 'code']);

        return view('tree_data.sequestered', compact('rows', 'treeCodes'));
    }

public function analyticsCarbon(Request $request)
{
    // === CARBON SEQUESTRATION ===
    $rows = \App\TreeData::with('treeCode', 'treeCode.treeType', 'treeCode.latestData')
        ->whereNotNull('annual_sequestration_kgco2')
        ->orderBy('tree_code_id')
        ->orderBy('id')
        ->get();

    $chartData = $rows->map(function ($r) {
        $tree = optional($r->treeCode);
        $latest = optional($tree->latestData);
        
        $typeName = optional($tree->treeType)->name;
        $typeName = $typeName ? strtoupper($typeName) : 'UNKNOWN';
        
        $dbh = $latest ? ($latest->dbh_cm ?? $latest->dbh ?? null) : null;
        $height = $latest ? ($latest->height_m ?? $latest->height ?? null) : null;
        
        return [
            'id' => $r->id,
            'label' => $tree->code ?? 'ID '.$r->id,
            'sequestration' => (float) ($r->annual_sequestration_kgco2 ?? 0),
            'type' => $typeName,
            'dbh' => $dbh ? (float) $dbh : null,
            'height' => $height ? (float) $height : null,
        ];
    });

    // === HARVEST ANALYTICS ===
    $typeFilterRaw = $request->query('type');
    $minDbh = $request->query('min_dbh');
    $maxDbh = $request->query('max_dbh');
    $minHeight = $request->query('min_height');
    $maxHeight = $request->query('max_height');

    $typeId = null;
    if ($typeFilterRaw !== null && $typeFilterRaw !== '') {
        if (is_numeric($typeFilterRaw)) {
            $typeId = (int) $typeFilterRaw;
        } else {
            $tt = \App\TreeType::whereRaw('UPPER(name) = ?', [strtoupper($typeFilterRaw)])->first();
            if ($tt) $typeId = $tt->id;
        }
    }

    $harvests = \App\Harvest::selectRaw('code, SUM(harvest_weight_kg) as total_kg')
        ->groupBy('code')
        ->get();

    $harvestCodes = $harvests->pluck('code')
    ->map(function ($c) {
        return (string) $c;
    })
    ->unique()
    ->values()
    ->all();


    $treeCodes = \App\TreeCode::whereIn('code', $harvestCodes)
        ->with(['treeType', 'latestData'])
        ->get()
        ->keyBy(function ($item) {
            return strtoupper($item->code);
        });

    $harvestData = $harvests->map(function ($h) use ($treeCodes) {
        $key = strtoupper($h->code);
        $tree = isset($treeCodes[$key]) ? $treeCodes[$key] : null;

        $typeName = optional(optional($tree)->treeType)->name;
        $typeName = $typeName ? strtoupper($typeName) : 'UNKNOWN';

        $latest = optional($tree)->latestData;
        $dbh = $latest ? ($latest->dbh_cm ?? $latest->dbh ?? null) : null;
        $height = $latest ? ($latest->height_m ?? $latest->height ?? null) : null;

        return [
            'code' => $tree ? $tree->code : $h->code,
            'type_id' => $tree ? $tree->tree_type_id : null,
            'type' => $typeName,
            'dbh' => $dbh ? (float) $dbh : null,
            'height' => $height ? (float) $height : null,
            'total_kg' => round($h->total_kg, 2),
        ];
    });

    $filtered = $harvestData->filter(function ($item) use ($typeId, $minDbh, $maxDbh, $minHeight, $maxHeight) {
        if ($typeId && ($item['type_id'] == null || $item['type_id'] != $typeId)) return false;
        if ($minDbh && $item['dbh'] && $item['dbh'] < $minDbh) return false;
        if ($maxDbh && $item['dbh'] && $item['dbh'] > $maxDbh) return false;
        if ($minHeight && $item['height'] && $item['height'] < $minHeight) return false;
        if ($maxHeight && $item['height'] && $item['height'] > $maxHeight) return false;
        return true;
    })->values();

    $order = [1 => 0, 2 => 1, 3 => 2];
    $sortedHarvest = $filtered->sortBy(function ($item) use ($order) {
        return $order[$item['type_id']] ?? 99;
    })->values();

    // === RETURN TO VIEW ===
    return view('analytics.carbon', [
        'chartData' => $chartData,          //  for sequestration chart with type/dbh/height
        'harvestData' => $sortedHarvest,    //  for harvest table or analytics
        'typeFilter' => $typeFilterRaw,
        'minDbh' => $minDbh,
        'maxDbh' => $maxDbh,
        'minHeight' => $minHeight,
        'maxHeight' => $maxHeight,
    ]);
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
                'tree_data_id' => $tree->id,
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