<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity; 
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function check(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        if (!Auth::attempt($credentials)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            return back()->with('error', 'Invalid credentials');
        }

        $user = Auth::user();

        // 🔹 Prevent inactive users from logging in
        if ($user->status !== 'active') {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is inactive. Please contact admin.'], 403);
            }

            return back()->with('error', 'Your account is inactive. Please contact admin.');
        }

        // 🔹 If API request → return token
        if ($request->expectsJson()) {
            $token = $user->createToken('mobile-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user'  => $user,
                'roles' => $user->getRoleNames(),
            ]);
        }

        // 🔹 Web login → redirect by role
        if ($user->hasRole('superadmin')) {
            return redirect()->route('superadmin.dashboard');
        } elseif ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('user')) {
            return redirect()->route('user.dashboard');
        }

        // fallback if role not valid
        Auth::logout();
        return back()->with('error', 'Invalid role assigned to your account.');
    }

    public function logout(Request $request)
    {
        // If API request → revoke token
        if ($request->expectsJson()) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        }

        // Otherwise → web logout
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

}