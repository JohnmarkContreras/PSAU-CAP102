<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        if ($user->hasRole('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('user')) {
            return redirect()->route('user.dashboard');
        }

        // fallback if role not found
        Auth::logout();
        return redirect()->route('login')->with('error', 'Invalid role assigned to your account.');
    }
}
