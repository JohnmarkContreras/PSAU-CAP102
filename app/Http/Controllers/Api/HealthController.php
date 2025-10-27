<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HealthController extends Controller
{
    public function ping(Request $r)
    {
        return response()->json([
            'pong'    => true,
            'time'    => now()->toISOString(),
            'path'    => $r->path(),
            'auth'    => optional($r->user())->only(['id','email']),
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function preflight()
    {
        return response()->noContent();
    }
}