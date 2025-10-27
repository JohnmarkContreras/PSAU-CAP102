@extends('layouts.app')

@section('title', 'Tamarind Trees')

@section('content')
<div 
    x-data="{ 
        showViewModal: false, 
        showUpdateModal: false, 
        modalData: null 
    }"
    x-on:open-view.window="modalData = $event.detail; showViewModal = true"
    x-on:open-update.window="modalData = $event.detail; showUpdateModal = true"
>
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Recorded Trees">
            <div class="text-sm text-black/90 space-y-0.5">
                @if($trees->isEmpty())
                    <p>No tree records available.</p>
                @else
                    <div class="overflow-x-auto">
                        <table id="treeTable" class="min-w-full text-sm text-left border border-gray-200 rounded-lg mt-2">
                            <thead class="bg-gray-100 text-center">
                                <tr>
                                    <th>Code</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>DBH (cm)</th>
                                    <th>Height (m)</th>
                                    <th>Age (yrs)</th>
                                    <th>Canopy (m)</th>
                                    <th>Status</th>
                                    <th colspan="3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @foreach($trees as $tree)
                                <tr>
                                    <td>{{ $tree->treeCode->code ?? 'N/A' }}</td>
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
                                            {{ ucfirst($tree->status ?? 'Unknown') }}
                                        </span>
                                    </td>

                                    <!-- View -->
                                    <td>
                                        <button 
                                            @click="$dispatch('open-view', {{ json_encode($tree) }})"
                                            class="bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">
                                            View
                                        </button>
                                    </td>

                                    <!-- Update -->
                                    <td>
                                        <button 
                                            @click="$dispatch('open-update', {{ json_encode($tree) }})"
                                            class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">
                                            Update
                                        </button>
                                    </td>

                                    <!-- Delete -->
                                    <td>
                                        <form action="{{ route('superadmin.destroyTreeData', $tree->id) }}" method="POST" style="display:inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are you sure you want to delete this tree?')"
                                                class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700">
                                                Delete
                                            </button>
                                        </form>
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

    <!-- View Modal -->
    <div 
        x-show="showViewModal" 
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
        @click.away="showViewModal = false"
        @keydown.escape.window="showViewModal = false"
        style="display: none;"
    >
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full relative">
            <button 
                @click="showViewModal = false"
                class="absolute top-2 right-2 text-black text-2xl font-bold rounded-full px-2"
                aria-label="Close"
            >&times;</button>

            <h2 class="text-lg font-semibold mb-3">Tree Details</h2>
            <template x-if="modalData">
                <div class="text-sm space-y-2">
                    <p><strong>Code:</strong> <span x-text="modalData.tree_code_id"></span></p>
                    <p><strong>Latitude:</strong> <span x-text="modalData.latitude"></span></p>
                    <p><strong>Longitude:</strong> <span x-text="modalData.longitude"></span></p>
                    <p><strong>DBH (cm):</strong> <span x-text="modalData.dbh ?? '—'"></span></p>
                    <p><strong>Height (m):</strong> <span x-text="modalData.height ?? '—'"></span></p>
                    <p><strong>Age (yrs):</strong> <span x-text="modalData.age ?? '—'"></span></p>
                    <p><strong>Canopy (m):</strong> <span x-text="modalData.canopy_diameter ?? '—'"></span></p>
                    <p><strong>Status:</strong> <span x-text="modalData.status ?? '—'"></span></p>
                </div>
            </template>
        </div>
    </div>

    <!-- Update Modal -->
    <div 
        x-show="showUpdateModal" 
        x-transition.opacity
        class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
        @click.away="showUpdateModal = false"
        @keydown.escape.window="showUpdateModal = false"
        style="display: none;"
    >
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full relative">
            <button 
                @click="showUpdateModal = false"
                class="absolute top-2 right-2 text-black text-2xl font-bold rounded-full px-2"
                aria-label="Close"
            >&times;</button>

            <h2 class="text-lg font-semibold mb-3">Update Tree Data</h2>
            <template x-if="modalData">
                <form method="POST" :action="'/tree-data/' + modalData.id">
                    @csrf
                    @method('PUT')
                    <div class="space-y-3 text-sm">
                        <input type="hidden" name="tree_code_id" :value="modalData.tree_code_id">
                        <div>
                            <label class="block text-gray-600">DBH (cm)</label>
                            <input type="number" step="0.01" name="dbh" x-model="modalData.dbh" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-gray-600">Height (m)</label>
                            <input type="number" step="0.01" name="height" x-model="modalData.height" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-gray-600">Age (yrs)</label>
                            <input type="number" name="age" x-model="modalData.age" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-gray-600">Canopy Diameter (m)</label>
                            <input type="number" step="0.01" name="canopy_diameter" x-model="modalData.canopy_diameter" class="w-full border rounded px-2 py-1">
                        </div>
                        <div>
                            <label class="block text-gray-600">Carbon Stock (kg)</label>
                            <input type="number" step="0.01" name="carbon_stock_kg" x-model="modalData.carbon_stock_kg" class="w-full border rounded px-2 py-1">
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if ($.fn.DataTable.isDataTable('#treeTable')) {
        $('#treeTable').DataTable().clear().destroy();
    }

    $('#treeTable').DataTable({
        responsive: true,
        pageLength: 10,
        ordering: true,
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search trees..."
        }
    });

    @if(session('success'))
        showToast('success', 'Success!', '{{ session('success') }}');
    @elseif(session('status'))
        showToast('info', 'Notice', '{{ session('status') }}');
    @endif
});
</script>
@endpush
