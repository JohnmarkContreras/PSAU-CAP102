<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Requests\CreateAccountRequest;
use App\Services\UserAccountService;
use App\Services\CarbonTrackingService;
use Illuminate\Support\Facades\DB;
use App\TreeData;
use Illuminate\Http\Request;
class SuperAdminController extends Controller
{
    private $accountService;
    private $carbonService;

    public function __construct(
        UserAccountService $accountService,
        CarbonTrackingService $carbonService
    ) {
        $this->accountService = $accountService;
        $this->carbonService = $carbonService;
    }

    /**
     * Display a listing of the tree data.
     */
    public function farmData()
    {
        $trees = TreeData::with('treeCode')->latest()->get();
        return view('pages.farm-data', compact('trees'));
    }

    /**
     * Store a newly created tree data.
     */
    public function storeTreeData(Request $request)
    {
        $request->validate([
            'tree_code_id' => 'required|exists:tree_code,id',
            'dbh' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'age' => 'nullable|integer|min:0',
            'planted_at' => 'nullable|date',
            'planted_year_only' => 'nullable|boolean',
            'stem_diameter' => 'nullable|numeric|min:0',
            'canopy_diameter' => 'nullable|numeric|min:0',
            'estimated_biomass_kg' => 'nullable|numeric|min:0',
            'carbon_stock_kg' => 'nullable|numeric|min:0',
            'annual_sequestration_kgco2' => 'nullable|numeric|min:0',
            'harvests' => 'nullable|integer|min:0',
        ]);

        TreeData::create($request->all());

        return redirect()->route('pages.farm-data')
            ->with('success', 'Tree data added successfully!');
    }

    /**
     * Update the specified tree data.
     */
    public function updateTreeData(Request $request, TreeData $treeData)
    {
        $request->validate([
            'tree_code_id' => 'required|exists:tree_code,id',
            'dbh' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'age' => 'nullable|integer|min:0',
            'planted_at' => 'nullable|date',
            'planted_year_only' => 'nullable|boolean',
            'stem_diameter' => 'nullable|numeric|min:0',
            'canopy_diameter' => 'nullable|numeric|min:0',
            'estimated_biomass_kg' => 'nullable|numeric|min:0',
            'carbon_stock_kg' => 'nullable|numeric|min:0',
            'annual_sequestration_kgco2' => 'nullable|numeric|min:0',
            'harvests' => 'nullable|integer|min:0',
        ]);

        $treeData->update($request->all());

        return redirect()->route('pages.farm-data')
            ->with('success', 'Tree data updated successfully!');
    }

    /**
     * Remove the specified tree data.
     */
    public function destroyTreeData(TreeData $treeData)
    {
        $treeData->delete();

        return redirect()->route('pages.farm-data')
            ->with('success', 'Tree data deleted successfully!');
    }

    public function analytics()
    {
        $chartData = $this->carbonService->generateChartData();
        
        return view('pages.analytics', [
            'role' => 'superadmin',
            'chartData' => $chartData
        ]);
    }

    public function harvestManagement()
    {
        return view('pages.harvest-management', ['role' => 'superadmin']);
    }

    public function accounts()
    {
        $users = \App\User::all();
                
        return view('pages.accounts', compact('users'));
    }

    public function deleteAccount($id)
    {
        if (!$this->accountService->canDeleteUser($id)) {
            return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $this->accountService->deleteUser($id);
        
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function createAccount()
    {
        return view('superadmin.create-account', ['role' => 'superadmin']);
    }

    public function storeAccount(CreateAccountRequest $request)
    {
        $this->accountService->createUser($request->validated());
        
        return redirect()->route('create.account')->with('success', 'User account created successfully.');
    }
}