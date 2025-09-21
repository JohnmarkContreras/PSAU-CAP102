<?php

namespace App\Http\Controllers;

use App\Tree;
use App\Harvest;
use App\Http\Requests\HarvestStoreRequest;
use App\Http\Requests\HarvestImportRequest;
use App\Services\HarvestPredictionService;
use App\Services\HarvestImportService;

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
        $data = [
            'allTrees' => Tree::orderBy('code')->get(),
            'trees' => Tree::with(['harvests', 'latestPrediction'])->orderBy('code')->paginate(10),
            'harvests' => Harvest::with('tree')->latest('harvest_date')->paginate(10)
        ];

        return view('pages.harvest-management', $data);
    }

    public function store(HarvestStoreRequest $request)
    {
        Harvest::create($request->validated());
        
        return back()->with('success', 'Harvest record added.');
    }

    public function import(HarvestImportRequest $request)
    {
        $this->importService->import($request->file('file'));
        
        return back()->with('success', 'Excel data imported.');
    }

    public function predictAll()
    {
        $results = $this->predictionService->predictAllTrees();
        
        return response()->json([
            'ok' => true,
            'results' => $results
        ]);
    }
}