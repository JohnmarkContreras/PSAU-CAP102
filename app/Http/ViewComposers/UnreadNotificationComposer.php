<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class UnreadNotificationComposer
{
    public function compose(View $view)
    {
        $unreadCount = Auth::check() ? Auth::user()->unreadNotifications()->count() : 0;
        $view->with('unreadCount', $unreadCount);
    }
}
