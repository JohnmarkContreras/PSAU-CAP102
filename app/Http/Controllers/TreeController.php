<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\TreesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Tree;

class TreeController extends Controller
{

    public function index()
    {
    $sweet = Tree::where('type', 'sweet')->count();
    $semi  = Tree::where('type', 'semi_sweet')->count();
    $sour  = Tree::where('type', 'sour')->count();
    $total = $sweet + $semi + $sour;

    // Add carbon stats per tree
    $trees = Tree::all()->map(function ($tree) {
        $biomass = 0.25 * pow($tree->stem_diameter, 2) * $tree->height;
        $carbon_stock = $biomass * 0.5;
        $annual_sequestration = $carbon_stock * 0.07;

        $tree->estimated_biomass_kg = round($biomass, 2);
        $tree->carbon_stock_kg = round($carbon_stock, 2);
        $tree->annual_sequestration_kg = round($annual_sequestration, 2);

        return $tree;
    });

    $chartData = $trees->map(fn($t) => [
        'code' => $t->code ?? 'Tree ' . $t->id,
        'sequestration' => $t->annual_sequestration_kg ?? 0,
    ])->toArray();

    return view('pages.analytics', compact(
        'sweet', 'semi', 'sour', 'total', 'trees', 'chartData'
    ));
    }
    
    public function importForm()
    {
        return view('trees.import');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);
        // Tree::truncate();
        Excel::import(new TreesImport, $request->file('file'));

        return redirect()->back()->with('success', 'Tamarind trees imported successfully!');
    }

    public function showMap()
    {
        return view('trees.map');
    }

    public function getTreeData()
    {
        return response()->json(Tree::all());
    }
}
