@extends('layouts.app')

@section('title', 'Pending Tags')

@section('content')
<div 
    x-data="{ showModal: false, modalImg: '' }" 
    x-on:open-modal.window="modalImg = $event.detail; showModal = true"
>
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Pending Geotag Trees">
            <div class="text-sm text-black/90 space-y-0.5">
                @if($pending->isEmpty())
                    <p>No pending geotags.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="pendingTable" class="min-w-full text-sm text-left border border-gray-200 rounded-lg mt-2">
                            <thead class="bg-gray-100 text-center">
                                <tr>
                                    <th>Image</th>
                                    <th>Code</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>DBH (cm)</th>
                                    <th>Height (m)</th>
                                    <th>Age (yrs)</th>
                                    <th>Canopy (m)</th>
                                    <th>Status</th>
                                    <th colspan="2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @foreach($pending as $tree)
                                <tr>
                                    <td>
                                        <img 
                                            src="{{ asset('storage/'.$tree->image_path) }}" 
                                            class="preview-image w-20 h-20 object-cover cursor-pointer transition hover:scale-105 mx-auto"
                                            data-img="{{ asset('storage/'.$tree->image_path) }}"
                                            alt="Tree Image"
                                        />
                                    </td>
                                    <td>{{ $tree->code }}</td>
                                    <td>{{ $tree->latitude }}</td>
                                    <td>{{ $tree->longitude }}</td>
                                    <td>{{ $tree->dbh ?? '—' }}</td>
                                    <td>{{ $tree->height ?? '—' }}</td>
                                    <td>{{ $tree->age ?? '—' }}</td>
                                    <td>{{ $tree->canopy_diameter ?? '—' }}</td>
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
                                            <button type="submit" class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Approve</button>
                                        </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if($tree->status === 'pending')
                                        <form action="{{ route('pending-geotags.reject', $tree->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            <input type="text" name="rejection_reason" placeholder="Reason" class="border rounded px-2 py-1 text-xs mr-2" required>
                                            <button type="submit" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">Reject</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </x-card>
    </section>

    <!-- Modal for image preview -->
    <div 
        x-show="showModal" 
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
        @click.away="showModal = false"
        @keydown.escape.window="showModal = false"
        style="display: none;"
    >
        <div class="relative">
            <img :src="modalImg" class="max-h-[80vh] max-w-[90vw] rounded shadow-lg border-4 border-white" alt="Preview">
            <button 
                @click="showModal = false"
                class="absolute top-2 right-2 text-white text-3xl font-bold bg-black bg-opacity-50 rounded-full px-2"
                aria-label="Close"
            >&times;</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ✅ Initialize DataTable
    if ($.fn.DataTable.isDataTable('#pendingTable')) {
        $('#pendingTable').DataTable().clear().destroy();
    }

    const table = $('#pendingTable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: true,
        columnDefs: [
            { orderable: false, targets: [0, 5, 6] }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search trees..."
        }
    });

    // ✅ Delegated handler for image preview
    $(document).on('click', '.preview-image', function (e) {
        e.preventDefault();
        const imgUrl = $(this).data('img');
        window.dispatchEvent(new CustomEvent('open-modal', { detail: imgUrl }));
    });

    // ✅ Show success toast from session
    @if(session('success'))
        showToast('success', 'Success!', '{{ session('success') }}');
    @elseif(session('status'))
        showToast('info', 'Notice', '{{ session('status') }}');
    @endif
});
</script>
@endpush
