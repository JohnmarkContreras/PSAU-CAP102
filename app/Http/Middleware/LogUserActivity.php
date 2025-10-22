<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
            $user = auth()->user();
            $path = $request->path();
            $method = $request->method();

            // Skip some routes if needed
            if (!in_array($path, ['login', 'logout', 'register'])) {
                activity('user_action')
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                        'method' => $method,
                        'user_agent' => $request->header('User-Agent'),
                    ])
                    ->log("{$user->name} accessed {$path} via {$method}");
            }
        }
        return $response;
    }
}
