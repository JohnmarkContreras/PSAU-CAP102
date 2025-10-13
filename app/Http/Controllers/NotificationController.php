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

    // Start building the query for notifications
    $query = $user->notifications()->latest();

    // Apply filter conditions
    if ($filter === 'unread') {
        $query = $user->unreadNotifications()->latest();
    } elseif ($filter === 'new') {
        $query = $user->notifications()
            ->whereDate('created_at', now()->toDateString())
            ->latest();
    }

    //  Only now, after all filters, paginate
    $notifications = $query->paginate(10);

    // 🔒 Role-based filtering for 'user' role
    if ($user->hasRole('user')) {
        $notifications->getCollection()->transform(function ($notification) {
            if (in_array($notification->type, [
                'App\Notifications\GeotagStatusChanged',
                'App\Notifications\FeedbackStatusUpdated',
            ])) {
                return $notification;
            }
            return null;
        })->filter();
    }

    // AJAX partials (for live filtering)
    if ($request->ajax()) {
        return view('partials.notifications.filter', compact('notifications'));
    }

    // Normal full page
    return view('pages.notifications.filter', compact('notifications', 'filter'));
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