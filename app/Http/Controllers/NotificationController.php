<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\User;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $user = auth()->user();

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'new') {
            $query->whereDate('created_at', now()->toDateString());
        }

        if ($user->hasRole('user')) {
            $query->whereIn('type', [
                'App\Notifications\GeotagStatusChanged',
                'App\Notifications\FeedbackStatusUpdated',
            ]);
        }

        // Only one unread count query - remove the loadCount() call
        $notifications = $query->paginate(10);

        if ($request->ajax()) {
            return view('partials.notifications', compact('notifications'));
        }

        return view('pages.Notifications', compact('notifications', 'filter'));
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