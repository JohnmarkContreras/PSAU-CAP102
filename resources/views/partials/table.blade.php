<div class="flex flex-col min-h-[600px]">
    <table class="w-full h-full text-sm text-left border border-gray-200 rounded-lg mt-2">
        <thead class="bg-gray-100">
            <tr class="text-center">
                <th>Image</th>
                <th>Code</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Status</th>
                <th colspan="2">Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($pending as $tree)
            <tr class="hover:bg-gray-50 text-center">
                <td>
                    <img 
                        src="{{ asset('storage/'.$tree->image_path) }}" 
                        class="w-20 h-20 object-cover cursor-pointer transition hover:scale-105"
                        @click="showModal = true; modalImg = '{{ asset('storage/'.$tree->image_path) }}'"
                        alt="Tree Image"
                    />
                </td>
                <td>{{ $tree->code }}</td>
                <td>{{ $tree->latitude }}</td>
                <td>{{ $tree->longitude }}</td>
                <td>
                    <span class="px-2 py-1 rounded text-xs font-bold
                        @if($tree->status === 'pending') bg-yellow-200 text-yellow-800
                        @elseif($tree->status === 'approved') bg-green-200 text-green-800
                        @else bg-red-200 text-red-800 @endif">
                        {{ ucfirst($tree->status) }}
                    </span>
                </td>
                <td>
                    @if($tree->status === 'pending')
                    <form action="{{ route('pending-geotags.approve', $tree->id) }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded">Approve</button>
                    </form>
                    @endif
                </td>
                <td>
                    @if($tree->status === 'pending')
                    <form action="{{ route('pending-geotags.reject', $tree->id) }}" method="POST" style="display:inline">
                        @csrf
                        <input type="text" name="rejection_reason" placeholder="Reason" class="border rounded px-2 py-1 text-xs mr-2" required>
                        <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded">Reject</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if ($pending->hasPages())
        <div class="pagination mt-4">
            {{ $pending->withQueryString()->links('pagination::tailwind') }}
        </div>
    @endif
</div>