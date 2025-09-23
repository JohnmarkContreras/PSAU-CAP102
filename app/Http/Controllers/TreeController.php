<?php

namespace App\Http\Controllers;

use App\Tree;
use App\PendingGeotag;
use App\Http\Requests\TreeStoreRequest;
use App\Http\Requests\TreeImportRequest;
use App\Services\TreeAnalyticsService;
use App\Services\TreeImportService;
use App\Services\TreeGeolocationService;

class TreeController extends Controller
{
    private $analyticsService;
    private $importService;
    private $geolocationService;
    protected $treeService;

    public function __construct(
        TreeAnalyticsService $analyticsService,
        TreeImportService $importService,
        TreeGeolocationService $geolocationService
    ) {
        $this->analyticsService = $analyticsService;
        $this->importService = $importService;
        $this->geolocationService = $geolocationService;
    }

    public function index()
    {
        $analytics = $this->analyticsService->getAnalyticsData();
        
        return view('pages.analytics', $analytics);
    }

    public function getCodes()
    {
        return response()->json(Tree::pluck('code'));
    }

    public function checkCode($code)
    {
        $exists = Tree::where('code', $code)->exists();
        
        return response()->json(['exists' => $exists]);
    }

    public function pending()
    {
        $pending = PendingGeotag::where('status', 'pending')->get();
        
        return view('geotags.pending', compact('pending'));
    }

    public function store(TreeStoreRequest $request)
    {
        $this->geolocationService->createPendingGeotag($request->validated());
        
        return redirect()->back()->with('success', 'Tree added successfully!');
    }

    public function importForm()
    {
        return view('trees.import');
    }

    public function importExcel(TreeImportRequest $request)
    {
        $this->importService->import($request->file('file'));
        
        return redirect()->back()->with('success', 'Tamarind trees imported successfully!');
    }

    public function getTreeData()
    {
        return response()->json(Tree::with('harvests')->get());
    }
}