<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Traits\LogsActivity; 
use Illuminate\Support\Facades\Auth;
use App\User;
class LoginController extends Controller
{

    protected function authenticated(Request $request, $user)
    {
        // already regenerated session in check(), but safe to ensure here too
        $request->session()->regenerate();

        if ($user->hasRole('superadmin')) {
            return redirect()->intended(route('superadmin.dashboard'));
        }
        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }
        if ($user->hasRole('user')) {
            return redirect()->intended(route('user.dashboard'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Invalid role assigned to your account.');
    }

    /**
     * Determine where to redirect users after login.
     *
     * Laravel will call this if a redirect is needed by its auth flow.
     */
    protected function redirectTo()
    {
        $user = auth()->user();

        if (! $user) {
            return route('login');
        }

        if ($user->hasRole('superadmin')) {
            return route('superadmin.dashboard');
        }

        if ($user->hasRole('admin')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('user')) {
            return route('user.dashboard');
        }

        auth()->logout();
        return route('login');
    }

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

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            return back()->with('error', 'Invalid credentials');
        }

        // Regenerate session to prevent fixation and ensure session is kept
        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is inactive. Please contact admin.'], 403);
            }

            return back()->with('error', 'Your account is inactive. Please contact admin.');
        }

        if ($request->expectsJson()) {
            $token = $user->createToken('mobile-token')->plainTextToken;
            return response()->json([
                'token' => $token,
                'user'  => $user,
                'roles' => $user->getRoleNames(),
            ]);
        }

        // Redirect by role, but respect intended URL if present
        if ($user->hasRole('superadmin')) {
            return redirect()->intended(route('superadmin.dashboard'));
        } elseif ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        } elseif ($user->hasRole('user')) {
            return redirect()->intended(route('user.dashboard'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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