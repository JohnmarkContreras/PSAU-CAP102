<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class NotificationController extends Controller
{
    
public function index(Request $request)
{
    $filter = $request->get('filter', 'all');
    $user = auth()->user();

    if ($filter === 'unread') {
        $notifications = $user->unreadNotifications()->latest()->get();
    } elseif ($filter === 'new') {
        $notifications = $user->notifications()
            ->whereDate('created_at', now()->toDateString())
            ->latest()->get();
    } else {
        $notifications = $user->notifications()->latest()->get();
    }

    // 🔒 Role-based filtering for 'user' role
    if ($user->hasRole('user')) {
        $notifications = $notifications->filter(function ($notification) {
            return in_array($notification->type, [
                'App\Notifications\GeotagStatusChanged',
                'App\Notifications\FeedbackStatusUpdated',
            ]);
        });
    }

    if ($request->ajax()) {
        return view('partials.notifications', compact('notifications'));
    }

    return view('pages.notifications', compact('notifications', 'filter'));
}

    

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect()->back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy($id)
    {
        auth()->user()->notifications()->findOrFail($id)->delete();
        return back()->with('success', 'Notification deleted.');
    }
}