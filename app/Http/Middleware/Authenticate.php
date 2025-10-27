<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * For API/JSON requests, do NOT redirect—return 401 JSON instead.
     * For web requests, you can still redirect to the login page.
     */
    protected function redirectTo($request)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return null; // causes AuthenticationException → 401 JSON
        }

        // Adjust this if your login route name/path is different:
        return route('login');
    }
}