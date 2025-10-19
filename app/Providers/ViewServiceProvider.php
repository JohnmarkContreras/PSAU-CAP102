<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\ViewComposers\UnreadNotificationComposer;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', UnreadNotificationComposer::class);
    }

    public function register()
    {
        //
    }
}
