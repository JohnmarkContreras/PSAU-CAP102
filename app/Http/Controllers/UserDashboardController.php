<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index()
    {
        $role = 'user';
        return view('pages.dashboard', compact('role'));
    }

    public function farmData()
    {
        $role = 'user';
        return view('pages.farm-data', compact('role'));
    }
    
    public function analytics()
    {
        $role = 'user';
        return view('pages.analytics', compact('role'));
    }

    public function harvestManagement()
    {
        $role = 'user';
        return view('pages.harvest-management', compact('role'));
    }

    public function backup()
    {
        $role = 'user';
        return view('pages.backup', compact('role'));
    }

    public function feedback()
    {
        $role = 'user';
        return view('pages.feedback', compact('role'));
    }

}
