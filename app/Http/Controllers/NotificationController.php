<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

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

        $notifications = $query->paginate(10);

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ]
            ]);
        }

        // Return view with initial data
        return view('pages.Notifications', compact('notifications', 'filter'));
    }

    public function markAsRead($id)
    {
        try {
            $notification = auth()->user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read.'
            ], 500);
        }
    }

    public function markAllRead()
    {
        try {
            auth()->user()->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all as read.'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            auth()->user()->notifications()->findOrFail($id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification.'
            ], 500);
        }
    }
}