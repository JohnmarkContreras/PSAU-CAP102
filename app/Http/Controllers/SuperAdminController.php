<?php

namespace App\Http\Controllers;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Tree;
class SuperAdminController extends Controller
{
    
    public function index()
    {
        $role = 'superadmin';
        return view('pages.dashboard', compact('role'));
    }

    public function farmData()
    {
        $role = 'superadmin';
        return view('pages.farm-data', compact('role'));
    }

    public function analytics()
    {
        $role = 'superadmin';
        return view('pages.analytics', compact('role'));

        foreach ($trees as $tree) {
        if (!CarbonRecord::whereDate('recorded_at', now())->where('tree_id', $tree->id)->exists()) {
            CarbonRecord::create([
                'tree_id' => $tree->id,
                'estimated_biomass_kg' => $tree->estimated_biomass_kg,
                'carbon_stock_kg' => $tree->carbon_stock_kg,
                'annual_sequestration_kg' => $tree->annual_sequestration_kg,
                'recorded_at' => now(),
            ]);
        }
    }
        $chartData = CarbonRecord::whereBetween('recorded_at', [$startDate, $endDate])
        ->get()
        ->groupBy('tree_id')
        ->map(fn($records, $treeId) => [
            'tree_code' => $records->first()->tree->code,
            'sequestration_series' => $records->pluck('annual_sequestration_kg'),
            'dates' => $records->pluck('recorded_at'),
        ]);


    }

    public function harvestManagement()
    {
        $role = 'superadmin';
        return view('pages.harvest-management', compact('role'));
    }

    public function backup()
    {
        $role = 'superadmin';
        return view('pages.backup', compact('role'));
    }

    public function feedback()
    {
        $role = 'superadmin';
        return view('pages.feedback', compact('role'));
    }

    public function accounts()
    {
        $users = User::all();
        return view('pages.accounts', compact('users'));
    }

    public function deleteAccount($id)
{
    $user = User::findOrFail($id);

    // Prevent deleting self
    if (auth()->id() == $id) {
        return redirect()->back()->withErrors(['error' => 'You cannot delete your own account.']);
    }

    $user->delete();
    return redirect()->back()->with('success', 'User deleted successfully.');
}

    public function createAccount()
{
    $role = 'superadmin';
    return view('superadmin.create-account', compact('role'));
}

public function storeAccount(Request $request)
{
    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:5',
        'role'     => 'required|in:user,admin',
    ]);

    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'password' => Hash::make($request->password),
        'role'     => $request->role,
    ]);

    return redirect()->route('create.account')->with('success', 'User account created successfully.');
}
}

