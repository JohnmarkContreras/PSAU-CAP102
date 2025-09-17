<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity; 
use Illuminate\Support\Facades\Auth;


class LoginController extends Controller
{
    // Show login form
    public function index()
    {
        return view('login'); // Make sure resources/views/login.blade.php exists
    }
    // Handle login form submission
    public function check(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials)) {
        $user = Auth::user();

        //Role-based redirects using Spatie
        if ($user->hasRole('superadmin')) {
            return redirect('/superadmin');
        } elseif ($user->hasRole('admin')) {
            return redirect('/admin');
        } elseif ($user->hasRole('user')) {
            return redirect('/user');
        } else {
            Auth::logout(); // Unknown role
            return redirect('/login')->withErrors(['role' => 'Unauthorized role.']);
        }
    }
    if (!Auth::attempt($credentials)) {
        return back()->with('error', 'Incorrect username or password')
                    ->withInput(); // ✅ this makes old('email') work
    }

    // Login failed
    return back()->withErrors([
        'email' => 'Invalid credentials.',
    ]);
    }
    // Logout method
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('success', 'You have been logged out.');
    }

    #activity log
    protected function authenticated(Request $request, $user)
    {
        activity()
            ->causedBy($user)
            ->log('Logged in');
    }
}
