<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\TreeAnalyticsService;
use App\Tree;
use App\Harvest;
use App\User;
use App\PendingGeotagTree;
use App\HarvestPrediction;
use App\TreeCode;
use App\TreeData;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Constructor to apply the 'auth' middleware
    public function __construct()
    {
        $this->middleware('auth');
    }


public function index(Request $request)
{
    $year = $request->input('year', now()->year); // default current year
    $month = $request->input('month'); // optional

    $pendingtree = PendingGeotagTree::where('status', 'pending')->count();
    $totalAnnualSequestrationKg = TreeData::query()->sum('annual_sequestration_kgco2');

    // Start harvest query
    $query = Harvest::query();

    if ($year) {
        $query->whereYear('harvest_date', $year);
    }
    if ($month) {
        $query->whereMonth('harvest_date', $month);
    }

    // Group actual harvests by month
    $harvestsByMonth = Harvest::selectRaw('MONTH(harvest_date) as month, SUM(harvest_weight_kg) as total_quantity')
        ->when($year, fn($q) => $q->whereYear('harvest_date', $year))
        ->groupBy('month')
        ->orderBy('month')
        ->paginate(50);

    // Format for Chart.js
    $months = $harvestsByMonth->pluck('month')->map(fn($m) =>
        \Carbon\Carbon::create()->month($m)->format('M')
    );
    $totals = $harvestsByMonth->pluck('total_quantity');

    // Total actual harvest overall
    $totalActual = $harvestsByMonth->sum('total_quantity');

    // Get filtered harvests
    $harvests = $query->with('tree')->orderBy('harvest_date', 'desc')->get();

    // Distinct years for filter dropdown
    $years = Harvest::selectRaw('YEAR(harvest_date) as year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year');

    // Other dashboard data
    $role = Auth::user()->getRoleNames()->first();
    $totaltrees = TreeCode::count();
    $totalsour = TreeCode::where('tree_type_id', '1')->count();
    $totalsweet = TreeCode::where('tree_type_id', '2')->count();
    $totalsemi_sweet = TreeCode::where('tree_type_id', '3')->count();
    $notifications = auth()->user()->notifications()->latest()->take(5)->get();

    $selectedYear = $year;
    $selectedMonth = $month;

    return view('pages.dashboard', compact(
        'role',
        'totaltrees',
        'totalsour',
        'totalsweet',
        'totalsemi_sweet',
        'notifications',
        'harvests',
        'years',
        'selectedYear',
        'selectedMonth',
        'pendingtree',
        'totalAnnualSequestrationKg',
        'months',
        'totals',
        'totalActual'
    ));
}   

        public function filterHarvests(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $query = Harvest::with('tree');

        if ($year) {
            $query->whereYear('harvest_date', $year);
        }
        if ($month) {
            $query->whereMonth('harvest_date', $month);
        }

        $harvests = $query->orderBy('harvest_date', 'desc')->get();

        return response()->json([
            'html' => view('partials.harvest-table', compact('harvests'))->render()
        ]);
    }


}
