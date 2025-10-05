<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Requests\CreateAccountRequest;
use App\Services\UserAccountService;
use App\Services\CarbonTrackingService;
use Illuminate\Support\Facades\DB;

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

    public function farmData()
    {
        return view('pages.farm-data', ['role' => 'superadmin']);
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