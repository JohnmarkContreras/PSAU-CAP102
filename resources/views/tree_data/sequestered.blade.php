@extends('layouts.app')

@section('title', 'Farm Data')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Trees with Carbon Sequestration">
                <div class="text-sm text-black/90 space-y-0.5">
                    <div class="flex items-center gap-3 mb-4">
                        <form id="filterForm" method="get" action="{{ route('tree_data.sequestered') }}" class="flex items-center gap-2">
                            <label for="tree_code_id" class="font-semibold">Filter by Tree Code</label>
                            <select name="tree_code_id" id="tree_code_id" class="border rounded px-2 py-1">
                                <option value="">All codes</option>
                                @foreach($treeCodes as $tc)
                                    <option value="{{ $tc->id }}" {{ request('tree_code_id') == $tc->id ? 'selected' : '' }}>
                                        {{ $tc->code }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded">Apply</button>
                        </form>

                        <div class="ml-auto flex items-center gap-2">
                            <a href="{{ route('tree_data.carbon') }}" class="text-green-700 hover:underline">Carbon Panel</a>
                            <form id="bulkForm" method="post" action="{{ route('tree_data.compute-carbon.bulk') }}" class="inline">
                                @csrf
                                <button type="button" id="bulkComputeBtn" class="bg-yellow-600 text-white px-3 py-1 rounded">Compute All</button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto bg-white rounded shadow">
                        <table class="min-w-full text-left">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2">ID</th>
                                    <th class="px-3 py-2">Tree Code</th>
                                    <th class="px-3 py-2">DBH (in)</th>
                                    <th class="px-3 py-2">DBH (cm)</th>
                                    <th class="px-3 py-2">Height (m)</th>
                                    <th class="px-3 py-2">Biomass (kg)</th>
                                    <th class="px-3 py-2">Carbon Stock (kg C)</th>
                                    <th class="px-3 py-2">Annual Sequestration (kg CO₂/yr)</th>
                                    <th class="px-3 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                    <tr class="border-b">
                                        <td class="px-3 py-2 align-top">{{ $row->id }}</td>
                                        <td class="px-3 py-2 align-top">{{ optional($row->treeCode)->code ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">{{ $row->dbh ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">{{ number_format(($row->dbh ?? 0) * 2.54, 2) }}</td>
                                        <td class="px-3 py-2 align-top">{{ $row->height ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">{{ $row->estimated_biomass_kg ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">{{ $row->carbon_stock_kg ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">{{ $row->annual_sequestration_kgco2 ?? '—' }}</td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('tree_data.carbon') }}?id={{ $row->id }}" class="text-blue-600 hover:underline">View</a>
                                                <button
                                                    class="recomputeBtn bg-yellow-500 text-white px-2 py-1 rounded text-sm"
                                                    data-id="{{ $row->id }}">
                                                    Recompute
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-3 py-4 text-center text-gray-600">No measurements with carbon data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $rows->links() }}
                    </div>
                </div>
            </x-card>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.recomputeBtn');
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = tokenMeta ? tokenMeta.getAttribute('content') : '';

            buttons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const id = this.dataset.id;
                    if (!confirm('Recompute and save carbon for measurement #' + id + '?')) return;
                    this.disabled = true;
                    fetch(`/tree_data/${id}/compute-carbon`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Network response was not ok');
                        return res.json();
                    })
                    .then(json => {
                        location.reload();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Error computing carbon');
                        this.disabled = false;
                    });
                });
            });

            const bulkBtn = document.getElementById('bulkComputeBtn');
            if (bulkBtn) {
                bulkBtn.addEventListener('click', function () {
                    if (!confirm('Compute and save carbon metrics for all filtered measurements?')) return;
                    bulkBtn.disabled = true;
                    fetch(`{{ route('tree_data.compute-carbon.bulk') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Bulk compute failed');
                        return res.json();
                    })
                    .then(json => {
                        alert('Updated ' + (json.updated || 0) + ' rows');
                        location.reload();
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Bulk compute failed');
                        bulkBtn.disabled = false;
                    });
                });
            }
        });
    </script>
@endsection