<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Tree;

class DashboardController extends Controller
{
    // Constructor to apply the 'auth' middleware
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $role = Auth::user()->role;
        $totaltrees = Tree::count();
        $totalsour = Tree::where('type', 'sour')->count();
        $totalsweet = Tree::where('type', 'sweet')->count();
        $totalsemi_sweet = Tree::where('type', 'semi_sweet')->count();
        $notifications = auth()->user()->notifications()->latest()->take(5)->get();
        return view('pages.dashboard', compact('role', 'totaltrees', 'totalsour', 'totalsweet', 'totalsemi_sweet', 'notifications'));
    }
}
