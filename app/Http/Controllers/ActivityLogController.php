<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Admin & Superadmin can see all logs
        if (in_array($user->role, ['admin', 'superadmin'])) {
            $logs = Activity::with('causer')
                ->latest()
                ->paginate(20);
        } 
        // Normal users see only their own logs
        else {
            $logs = Activity::where('causer_id', $user->id)
                ->with('causer')
                ->latest()
                ->paginate(20);
        }

        return view('pages.activity-log', compact('logs'));
    }
}
