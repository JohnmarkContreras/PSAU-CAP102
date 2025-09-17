<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\TreesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Tree;
use App\PendingGeotag;
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

    //display tree record
    $trees= \App\Tree::with('harvests')->get();
    return view('pages.analytics', compact(
        'sweet', 'semi', 'sour', 'total', 'trees', 'chartData'
    ));
    }
    
    public function getCodes()
    {
        $codes = Tree::pluck('code'); // returns collection of codes
        return response()->json($codes);
    }

    // Check if a code already exists (for duplicate prevention)
    public function checkCode(Request $request)
    {
        $exists = Tree::where('code', $request->code)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function pending()
    {
        // Fetch all pending geotag requests
        $pending = PendingGeotag::where('status', 'pending')->get();

        // Send them to the Blade view
        return view('geotags.pending', compact('pending'));
    }

    // Store manual entry
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:trees,code',
            'type' => 'required|in:sweet,sour,semi_sweet',
            'age' => 'required|integer|min:0',
            'height' => 'required|numeric|min:0',
            'stem_diameter' => 'required|numeric|min:0',
            'canopy_diameter' => 'required|numeric|min:0',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        PendingGeotag::create([
        'user_id' => auth()->id(),
        'code'            => strtoupper($request->code),
        'type'            => $request->type,
        'age'             => $request->age,
        'height'          => $request->height,
        'stem_diameter'   => $request->stem_diameter,
        'canopy_diameter' => $request->canopy_diameter,
        'latitude'        => $request->latitude,
        'longitude'       => $request->longitude,
        'status'          => 'pending',
    ]);


        return redirect()->back()->with('success', 'Tree added successfully!');
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

    

    public function getTreeData()
    {
        $trees = Tree::with('harvests')->get();
        return response()->json($trees);
    }
}
