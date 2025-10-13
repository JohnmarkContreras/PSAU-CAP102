@extends('layouts.app')

@section('title', 'Harvest Calendar')

@section('content')
<main id="dashboard-container" class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Harvest calendar">
            <div class="container mx-auto p-4">
                <h1 class="text-2xl font-bold mb-4">Upcoming Harvests</h1>

                <!-- Filters (client-side; update table without submitting) -->
                <div class="flex flex-wrap gap-4 mb-6 items-center">
                    <select id="filter-month" class="border rounded px-3 py-2">
                        <option value="">All Months</option>
                        @foreach(range(1,3) as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filter-type" class="border rounded px-3 py-2">
                        <option value="">All Types</option>
                        <option value="sweet" {{ request('type')=='sweet' ? 'selected' : '' }}>Sweet</option>
                        <option value="sour" {{ request('type')=='sour' ? 'selected' : '' }}>Sour</option>
                        <option value="semi_sweet" {{ request('type')=='semi_sweet' ? 'selected' : '' }}>Semi-Sweet</option>
                    </select>

                    <button id="clear-filters" class="bg-gray-200 text-gray-800 px-3 py-2 rounded hover:bg-gray-300">
                        Clear
                    </button>
                </div>

                <!-- Harvest List (DataTables) -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <table id="harvestsTable" class="w-full border text-sm stripe hover">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2">Tree Code</th>
                                <th class="border px-3 py-2">Type</th>
                                <th class="border px-3 py-2">Predicted Date</th>
                                <th class="border px-3 py-2">Predicted Quantity (kg)</th>
                                <th class="border px-3 py-2">Action</th>

                                <!-- Hidden helper columns used only for legacy; DataTables will hide them -->
                                <th style="display:none">month</th>
                                <th style="display:none">type_slug</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($harvests as $h)
                                @php
                                    $typeModel = optional($h->treeCode)->treeType;
                                    $typeSlug = optional($typeModel)->slug
                                        ?? (optional($typeModel)->name ? \Illuminate\Support\Str::slug(optional($typeModel)->name) : '');
                                    $rowMonth = \Carbon\Carbon::parse($h->predicted_date)->month;
                                @endphp

                                <tr data-prediction-id="{{ $h->id }}"
                                    data-month="{{ $rowMonth }}"
                                    data-type="{{ $typeSlug }}">
                                    <td class="border px-3 py-2">{{ $h->code }}</td>

                                    {{-- Display label for human readers --}}
                                    <td class="border px-3 py-2">
                                        {{ optional($typeModel)->name ?? 'Unknown' }}
                                    </td>

                                    <td class="border px-3 py-2">
                                        {{ \Carbon\Carbon::parse($h->predicted_date)->format('M d, Y') }}
                                    </td>

                                    <td class="border px-3 py-2">{{ $h->predicted_quantity }}</td>

                                    {{-- Action: Mark as done --}}
                                    <td class="border px-3 py-2 text-center">
                                        @if(optional($h)->status === 'done')
                                            <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-sm">Done</span>
                                        @else
                                            <button
                                                class="mark-done-btn bg-green-600 text-white px-3 py-1 rounded text-sm"
                                                data-prediction-id="{{ $h->id }}"
                                                data-code="{{ $h->code }}"
                                                data-predicted-date="{{ $h->predicted_date }}"
                                                data-predicted-quantity="{{ $h->predicted_quantity }}">
                                                Mark as done
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Hidden cells kept for compatibility (not used by filter) --}}
                                    <td style="display:none">{{ $rowMonth }}</td>
                                    <td style="display:none">{{ $typeSlug }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-gray-500">
                                        No upcoming harvests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- DataTables will show its own paging UI -->
            </div>
        </x-card>
    </section>
</main>

<!-- Modal: single instance -->
<div id="markDoneModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
<div class="bg-white rounded-lg w-full max-w-md p-6 mx-4">
    <h3 class="text-lg font-semibold mb-3">Mark harvest as done</h3>

    <form id="markDoneForm">
    @csrf
    <input type="hidden" name="prediction_id" id="prediction_id">

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Tree code</label>
        <input type="text" id="modal_code" class="w-full border rounded px-3 py-2" readonly>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Predicted date</label>
        <input type="text" id="modal_predicted_date" class="w-full border rounded px-3 py-2" readonly>
    </div>

    <div class="mb-3">
        <label class="block text-sm font-medium mb-1">Predicted quantity (kg)</label>
        <input type="text" id="modal_predicted_quantity" class="w-full border rounded px-3 py-2" readonly>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Actual harvested quantity (kg)</label>
        <input type="number" step="0.01" min="0" name="actual_quantity" id="actual_quantity" class="w-full border rounded px-3 py-2" required>
        <p id="modal_error" class="text-red-600 text-sm mt-1 hidden"></p>
    </div>

    <div class="flex justify-end gap-2">
        <button type="button" id="modalCancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Save</button>
    </div>
    </form>
</div>
</div>
@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable
    var table = $('#harvestsTable').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[2, 'asc']], // sort by predicted date display column index 2
        columnDefs: [
            { orderable: false, targets: [1, 3, 4] }, // Type label, quantity, action
            { targets: [5,6], visible: false, searchable: false } // hidden helper cols
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search harvests...",
            lengthMenu: "Show _MENU_ entries",
            paginate: { previous: "← Prev", next: "Next →" },
            info: "Showing _START_ to _END_ of _TOTAL_ harvests",
            infoEmpty: "No harvests available",
        }
    });

    // DOM-based custom filtering: month and type using row data attributes
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var selectedMonth = $('#filter-month').val();
        var selectedType = $('#filter-type').val();

        // get the row node and read data attributes
        var rowNode = table.row(dataIndex).node();
        if (!rowNode) return true; // fallback allow

        var rowMonth = rowNode.getAttribute('data-month');
        var rowType = (rowNode.getAttribute('data-type') || '').toString();

        // Month filter
        if (selectedMonth && selectedMonth !== '') {
            if (!rowMonth || parseInt(rowMonth, 10) !== parseInt(selectedMonth, 10)) {
                return false;
            }
        }

        // Type filter
        if (selectedType && selectedType !== '') {
            if (!rowType || rowType !== selectedType) {
                return false;
            }
        }

        return true;
    });

    // Redraw table when filters change
    $('#filter-month, #filter-type').on('change', function () {
        table.draw();
    });

    // Clear filters
    $('#clear-filters').on('click', function () {
        $('#filter-month').val('');
        $('#filter-type').val('');
        table.draw();
    });

    // Modal handling
    const modal = document.getElementById('markDoneModal');
    const form = document.getElementById('markDoneForm');
    const predictionIdInput = document.getElementById('prediction_id');
    const codeInput = document.getElementById('modal_code');
    const predictedDateInput = document.getElementById('modal_predicted_date');
    const predictedQtyInput = document.getElementById('modal_predicted_quantity');
    const actualQtyInput = document.getElementById('actual_quantity');
    const cancelBtn = document.getElementById('modalCancel');
    const modalError = document.getElementById('modal_error');

    // Open modal
    $('#harvestsTable').on('click', '.mark-done-btn', function () {
        const btn = $(this);
        predictionIdInput.value = btn.data('prediction-id');
        codeInput.value = btn.data('code');
        predictedDateInput.value = btn.data('predicted-date');
        predictedQtyInput.value = btn.data('predicted-quantity');
        actualQtyInput.value = '';
        modalError.classList.add('hidden');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    });

    // Cancel
    cancelBtn.addEventListener('click', function () {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    });

    // AJAX setup for CSRF
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    // Submit: create Harvest and mark prediction done
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        modalError.classList.add('hidden');
        const payload = {
            prediction_id: predictionIdInput.value,
            actual_quantity: actualQtyInput.value
        };

        // simple client validation
        if (!payload.actual_quantity || parseFloat(payload.actual_quantity) < 0) {
            modalError.textContent = 'Please enter a valid harvested quantity.';
            modalError.classList.remove('hidden');
            return;
        }

        $.post("{{ route('harvests.markDone') }}", payload)
            .done(function (res) {
                // close modal
                modal.classList.remove('flex');
                modal.classList.add('hidden');

                // update table row: remove or mark done
                var row = $('#harvestsTable').find('tr[data-prediction-id="' + payload.prediction_id + '"]');
                if (res.remove_row) {
                    table.row(row).remove().draw(false);
                } else if (res.updated_html) {
                    table.row(row).remove();
                    $('#harvestsTable tbody').append(res.updated_html);
                    table.draw(false);
                } else {
                    row.find('td').eq(4).html('<span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-sm">Done</span>');
                }

                // optional toast
                alert(res.message || 'Harvest recorded and prediction marked done.');
            })
            .fail(function (xhr) {
                var msg = xhr.responseJSON?.message || 'Failed to save harvest. Please try again.';
                modalError.textContent = msg;
                modalError.classList.remove('hidden');
            });
    });
});
</script>
@endpush