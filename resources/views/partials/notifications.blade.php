@forelse($notifications as $notification)
    <div class="bg-green-100 p-3 rounded mb-2">
        <p>{{ $notification->data['message'] ?? 'Notification received.' }}</p>

        @if(isset($notification->data['geotag_id']))
            <div class="h-12 flex justify-between items-center py-2">
                @if(isset($notification->data['geotag_id']))
                    @if(auth()->user()->hasAnyRole(['admin', 'superadmin']))
                            <a href="{{ route('geotags.pending', $notification->data['geotag_id']) }}" class="text-blue-600 underline">
                                View Geotag
                            </a>
                        @else
                            <span class="text-gray-500 italic">Geotag exists but you don’t have access</span>
                        @endif

                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-400 text-xs p-3 py-1 mb-4 hover:underline bg-red-100 rounded cursor-pointer">Delete</button>
                        </form>
                        @else
                            <span class="text-gray-500 italic">No geotag link available</span>
                        @endif
                    </div>
                @endif
        @if(is_null($notification->read_at))
            <form method="POST" action="{{ route('notifications.markAsRead', $notification->id) }}" class="inline-block ml-4">
                @csrf
                <button class="text-sm text-gray-600 hover:underline">Mark as read</button>
            </form>
        @endif
    </div>
@empty
    <p class="text-gray-500">No notifications found.</p>
@endforelse