<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tree;

class CarbonRecordController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tree_id' => 'required|exists:trees,id',
            'estimated_biomass_kg' => 'nullable|numeric',
            'carbon_stock_kg' => 'nullable|numeric',
            'annual_sequestration_kg' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $carbonRecord = CarbonRecord::create($validated);

        return response()->json([
            'message' => 'Carbon data recorded successfully.',
            'data' => $carbonRecord
        ], 201);
    }

    public function create()
    {
        $trees = Tree::all()->map(function ($tree) {
            $biomass = 0.25 * pow($tree->stem_diameter, 2) * $tree->height;
            $carbon_stock = $biomass * 0.5;
            $annual_sequestration = $carbon_stock * 0.07;

            $tree->estimated_biomass_kg = round($biomass, 2);
            $tree->carbon_stock_kg = round($carbon_stock, 2);
            $tree->annual_sequestration_kg = round($annual_sequestration, 2);

            return $tree;
        });

        $chartData = $trees->map(function ($tree) {
            return [
                'code' => $tree->code,
                'sequestration' => $tree->annual_sequestration_kg,
            ];
        })->toArray();

        return view('pages.carbon-records-create', compact('trees', 'chartData'));
    }

    public function showCreateForm()
    {
        $trees = Tree::all();
        return view('pages.carbon-record-create', compact('trees'));
    }
}

