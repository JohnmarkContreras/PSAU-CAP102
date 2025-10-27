<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class RoleRedirectController extends Controller
{
    public function handle()
    {
        $user = Auth::user();

        if ($user->hasRole('superadmin')) {
            return redirect('/superadmin');
        }

        if ($user->hasRole('admin')) {
            return redirect('/admin');
        }

        if ($user->hasRole('user')) {
            return redirect('/user');
        }

        abort(403, 'Unauthorized role');
    }
}
