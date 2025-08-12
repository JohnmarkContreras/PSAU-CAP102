<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $role = 'admin';
        return view ('pages.dashboard', compact('role'));
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

    public function feedback()
    {
        $role = 'admin';
        return view('pages.feedback', compact('role'));
    }

    public function test()
    {
        $role = 'admin';
        return view('pages.test', compact('role'));
    }
}