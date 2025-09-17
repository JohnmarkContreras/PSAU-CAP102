<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function dashboardUrl()
    {
        return '/' . $this->role . '/dashboard';
    }
    public function handle($request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::user();

                // Redirect based on role
                if ($user->role === 'superadmin') {
                    return redirect('/superadmin');
                } elseif ($user->role === 'admin') {
                    return redirect('/admin');
                } elseif ($user->role === 'user') {
                    return redirect('/user');
                }

                return redirect("/" . Auth::user()->role);
            }
        }

        // fallback check if no guards passed
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 'superadmin') {
                return redirect('/superadmin');
            } elseif ($user->role === 'admin') {
                return redirect('/admin');
            } elseif ($user->role === 'user') {
                return redirect('/user');
            }

        return redirect("/" . Auth::user()->role);
        }
        return $next($request);
    }

}