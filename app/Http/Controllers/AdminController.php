<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Tree;

class AdminController extends Controller
{
    public function index()
    {
        $role = 'admin';
        $totaltrees = Tree::count();
        $totalsour = Tree::where('type', 'sour')->count();
        $totalsweet = Tree::where('type', 'sweet')->count();
        $totalsemi_sweet = Tree::where('type', 'semi_sweet')->count();
        return view('pages.dashboard', compact('role', 'totaltrees', 'totalsour', 'totalsweet', 'totalsemi_sweet'));
    }

    public function farmData()
    {
        $role = 'admin';
        return view('pages.farm-data', compact('role'));
    }

    public function analytics()
    {
        $role = 'admin';
        return view('pages.analytics', compact('role'));
    }

    public function harvestManagement()
    {
        $role = 'admin';
        return view('pages.harvest-management', compact('role'));
    }
}