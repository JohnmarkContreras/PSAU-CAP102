<?php
use Illuminate\Support\Facades\Auth;
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Constructor to apply the 'auth' middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        return view('pages.dashboard');
    }

    public function farmData()
    {
        return view('pages.farm-data');
    }

    public function analytics()
    {
        return view('pages.analytics');
    }

    public function carbonSequestration()
    {
        return view('pages.carbon-sequestration');
    }

    public function harvestManagement()
    {
        return view('pages.harvest-management');
    }

    public function backup()
    {
        return view('pages.backup');
    }

    public function feedback()
    {
        return view('pages.feedback');
    }
}
