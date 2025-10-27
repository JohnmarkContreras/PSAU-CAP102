<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
    // Keep only the query logging for debugging
        if (app()->environment('local')) {
            DB::listen(function ($query) {
                if ($query->time > 100) {
                    Log::debug("Slow SQL: {$query->sql} [{$query->time} ms]");
                }
            });
        }
        
        View::composer('*', function ($view) {
        $unreadCount = 0;

        if (Auth::check()) {
            $unreadCount = Auth::user()->unreadNotifications()->count();
        }

        $view->with('unreadNotificationsCount', $unreadCount);
    });
    }
}