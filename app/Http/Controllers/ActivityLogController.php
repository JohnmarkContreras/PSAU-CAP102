<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Auth;


class ActivityLogController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'superadmin'])) {
            // Admin/Superadmin can see all logs
            $logs = Activity::with('causer')->latest()->get();
        } else {
            // Normal users only see their own logs
            $logs = Activity::where('causer_id', $user->id)
                            ->with('causer')
                            ->latest()
                            ->get();
        }

        return view('pages.activity-log', compact('logs'));
    }
}
