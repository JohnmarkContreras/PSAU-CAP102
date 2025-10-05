<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TreeCode;
use App\TreeMeasurement;


class TreeMeasurementController extends Controller
{

public function create(Request $request)
{
    $treeCode = null;

    // prefer numeric id param
    if ($request->filled('tree_code_id')) {
        $treeCode = \App\TreeCode::find($request->query('tree_code_id'));
    }

    // fallback: accept a code string param like ?tree_code=ABC-001
    if (! $treeCode && $request->filled('tree_code')) {
        $treeCode = \App\TreeCode::where('code', $request->query('tree_code'))->first();
    }

    // pass null if not found — view will handle optional($treeCode)
    return view('tree_measurements.create', compact('treeCode'));
}


    public function store(Request $request)
    {
        $validated = $request->validate([
            'tree_code_id' => 'required|exists:tree_code,id',
            'age' => 'nullable|integer|min:0',
            'height_m' => 'nullable|numeric|min:0',
            'canopy_diameter' => 'nullable|numeric|min:0',
            'stem_diameter' => 'nullable|numeric|min:0',
        ]);

        TreeMeasurement::create($validated);

        return redirect()->route('tree_measurements.index')->with('success', 'Measurement saved.');
    }

    public function index()
    {
        $measurements = TreeMeasurement::with('treeCode')->latest()->paginate(20);
        return view('tree_measurements.index', compact('measurements'));
    }
    
    public function storeByCode(Request $request)
    {
        $validated = $request->validate([
            'tree_code_id' => 'nullable|exists:tree_code,id',
            'tree_code' => 'nullable|string',
            'age' => 'nullable|integer|min:0',
            'height_m' => 'nullable|numeric|min:0',
            'canopy_diameter_m' => 'nullable|numeric|min:0',
            'stem_diameter_cm' => 'nullable|numeric|min:0',
        ]);

        if ($validated['tree_code_id'] ?? false) {
            $treeCodeId = $validated['tree_code_id'];
        } elseif (! empty($validated['tree_code'])) {
            $treeCode = \App\TreeCode::where('code', $validated['tree_code'])->first();
            $treeCodeId = $treeCode ? $treeCode->id : null;
        } else {
            $treeCodeId = null;
        }

        if (! $treeCodeId) {
            return back()->withErrors(['tree_code' => 'Tree code not found'])->withInput();
        }

        \App\TreeMeasurement::create(array_merge($validated, ['tree_code_id' => $treeCodeId]));
        return redirect()->route('tree_measurements.index')->with('success', 'Measurement saved.');
    }
}
