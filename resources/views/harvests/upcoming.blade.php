@extends('layouts.app')

@section('title', 'Harvest Calendar')

@section('content')
<main id="dashboard-container" class="flex-1 p-4 md:p-6 space-y-4 md:space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Add Harvest recods">
            <div class="container mx-auto p-2 md:p-4">
                {{-- Manual Entry --}}
                <form action="{{ route('harvest.store') }}" method="POST" class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium mb-1">Tree</label>
                        <select name="code" class="w-full border rounded-lg p-2">
                            @foreach($codes as $tc)
                                <option value="{{ $tc->code }}">{{ $tc->code }}</option>
                            @endforeach
                        </select>
                        @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Harvest Date</label>
                        <input type="date" name="harvest_date" class="w-full border rounded-lg p-2" required>
                        @error('harvest_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Weight (kg)</label>
                        <input type="number" step="0.01" name="harvest_weight_kg" class="w-full border rounded-lg p-2" required>
                        @error('harvest_weight_kg') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Quality</label>
                        <input type="text" name="quality" placeholder="A/B/C" class="w-full border rounded-lg p-2">
                    </div>
                    <div class="md:col-span-1 flex items-end">
                        <button class="w-full rounded-xl bg-blue-600 text-white py-2 px-4 hover:bg-blue-700">Add Harvest</button>
                    </div>
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium mb-1">Notes</label>
                        <textarea name="notes" class="w-full border rounded-lg p-2" rows="2"></textarea>
                    </div>
                </form>
            </div>
        </x-card>
        <x-card title="Harvest calendar">
            <div class="container mx-auto p-2 md:p-4">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Upcoming Harvests</h1>

                <!-- Filters: Responsive Stack -->
                <div class="flex flex-col md:flex-row flex-wrap gap-3 md:gap-4 mb-6 items-start md:items-center">
                    <select id="filter-month" class="border rounded px-3 py-2 w-full md:w-auto">
                        <option value="">All Months</option>
                        @foreach(range(1,3) as $m)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filter-type" class="border rounded px-3 py-2 w-full md:w-auto">
                        <option value="">All Types</option>
                        <option value="sweet" {{ request('type')=='sweet' ? 'selected' : '' }}>Sweet</option>
                        <option value="sour" {{ request('type')=='sour' ? 'selected' : '' }}>Sour</option>
                        <option value="semi_sweet" {{ request('type')=='semi_sweet' ? 'selected' : '' }}>Semi-Sweet</option>
                    </select>

                    <button id="clear-filters" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300 w-full md:w-auto">
                        Clear
                    </button>
                </div>

                <!-- Harvest List: Responsive DataTable with Horizontal Scroll -->
                <div class="bg-white shadow rounded-lg">
                    <div class="overflow-x-auto">
                        <table id="harvestsTable" class="w-full min-w-max border text-xs md:text-sm stripe hover">
                            <thead class="bg-gray-100 sticky top-0">
                                <tr>
                                    <th class="border px-2 md:px-3 py-2 whitespace-nowrap">Tree Code</th>
                                    <th class="border px-2 md:px-3 py-2 whitespace-nowrap">Type</th>
                                    <th class="border px-2 md:px-3 py-2 whitespace-nowrap">Predicted Date</th>
                                    <th class="border px-2 md:px-3 py-2 whitespace-nowrap">Qty (kg)</th>
                                    <th class="border px-2 md:px-3 py-2 whitespace-nowrap">Action</th>

                                <!-- Hidden helper columns -->
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
                                    <td class="border px-2 md:px-3 py-2 font-semibold whitespace-nowrap">{{ $h->code }}</td>

                                    <td class="border px-2 md:px-3 py-2 whitespace-nowrap">
                                        {{ optional($typeModel)->name ?? 'Unknown' }}
                                    </td>

                                    <td class="border px-2 md:px-3 py-2 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($h->predicted_date)->format('M d, Y') }}
                                    </td>

                                    <td class="border px-2 md:px-3 py-2 whitespace-nowrap">{{ $h->predicted_quantity }}</td>

                                    <td class="border px-2 md:px-3 py-2 text-center whitespace-nowrap">
                                        @if(optional($h)->status === 'done')
                                            <span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs md:text-sm">Done</span>
                                        @else
                                            <button
                                                class="mark-done-btn bg-green-600 text-white px-2 md:px-3 py-1 rounded text-xs md:text-sm whitespace-nowrap"
                                                data-prediction-id="{{ $h->id }}"
                                                data-code="{{ $h->code }}"
                                                data-predicted-date="{{ $h->predicted_date }}"
                                                data-predicted-quantity="{{ $h->predicted_quantity }}">
                                                Mark Done
                                            </button>
                                        @endif
                                    </td>

                                    <td style="display:none">{{ $rowMonth }}</td>
                                    <td style="display:none">{{ $typeSlug }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-gray-500 text-sm md:text-base">
                                        No upcoming harvests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>
    </section>
</main>

<!-- Modal: Single Instance -->
<div id="markDoneModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
<div class="bg-white rounded-lg w-full max-w-md p-4 md:p-6 max-h-screen overflow-y-auto">
    <h3 class="text-lg md:text-xl font-semibold mb-3 md:mb-4">Mark harvest as done</h3>

    <form id="markDoneForm">
    @csrf
    <input type="hidden" name="prediction_id" id="prediction_id">

    <div class="mb-3 md:mb-4">
        <label class="block text-sm font-medium mb-1">Tree code</label>
        <input type="text" id="modal_code" class="w-full border rounded px-3 py-2 text-sm" readonly>
    </div>

    <div class="mb-3 md:mb-4">
        <label class="block text-sm font-medium mb-1">Predicted date</label>
        <input type="text" id="modal_predicted_date" class="w-full border rounded px-3 py-2 text-sm" readonly>
    </div>

    <div class="mb-3 md:mb-4">
        <label class="block text-sm font-medium mb-1">Predicted quantity (kg)</label>
        <input type="text" id="modal_predicted_quantity" class="w-full border rounded px-3 py-2 text-sm" readonly>
    </div>

    <div class="mb-4 md:mb-5">
        <label class="block text-sm font-medium mb-1">Actual harvested quantity (kg)</label>
        <input type="number" step="0.01" min="0" name="actual_quantity" id="actual_quantity" class="w-full border rounded px-3 py-2 text-sm" required>
        <p id="modal_error" class="text-red-600 text-xs md:text-sm mt-1 hidden"></p>
    </div>

    <div class="flex flex-col md:flex-row justify-end gap-2 md:gap-3">
        <button type="button" id="modalCancel" class="px-4 py-2 bg-gray-200 rounded text-sm md:text-base hover:bg-gray-300 order-2 md:order-1">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded text-sm md:text-base hover:bg-green-700 order-1 md:order-2">Save</button>
    </div>
    </form>
</div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.js/1.13.6/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.js/1.13.6/dataTables.responsive.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize DataTable with Responsive
    var table = $('#harvestsTable').DataTable({
        responsive: false,
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        order: [[2, 'asc']],
        columnDefs: [
            { orderable: false, targets: [1, 3, 4] },
            { targets: [5, 6], visible: false, searchable: false }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search harvests...",
            lengthMenu: "Show _MENU_ entries",
            paginate: { previous: "← Prev", next: "Next →" },
            info: "Showing _START_ to _END_ of _TOTAL_ harvests",
            infoEmpty: "No harvests available",
        },
        dom: '<"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4"<"md:order-2"f><"md:order-1"l>><"overflow-x-auto"t><"flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-4"ip>'
    });

    // Custom filtering
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        var selectedMonth = $('#filter-month').val();
        var selectedType = $('#filter-type').val();
        var rowNode = table.row(dataIndex).node();
        
        if (!rowNode) return true;

        var rowMonth = rowNode.getAttribute('data-month');
        var rowType = (rowNode.getAttribute('data-type') || '').toString();

        if (selectedMonth && selectedMonth !== '') {
            if (!rowMonth || parseInt(rowMonth, 10) !== parseInt(selectedMonth, 10)) {
                return false;
            }
        }

        if (selectedType && selectedType !== '') {
            if (!rowType || rowType !== selectedType) {
                return false;
            }
        }

        return true;
    });

    $('#filter-month, #filter-type').on('change', function () {
        table.draw();
    });

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
        actualQtyInput.focus();
    });

    cancelBtn.addEventListener('click', function () {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    });

    // Close modal on outside click
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        modalError.classList.add('hidden');
        
        const payload = {
            prediction_id: predictionIdInput.value,
            actual_quantity: actualQtyInput.value
        };

        if (!payload.actual_quantity || parseFloat(payload.actual_quantity) < 0) {
            modalError.textContent = 'Please enter a valid harvested quantity.';
            modalError.classList.remove('hidden');
            return;
        }

        $.post("{{ route('harvests.markDone') }}", payload)
            .done(function (res) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');

                var row = $('#harvestsTable').find('tr[data-prediction-id="' + payload.prediction_id + '"]');
                if (res.remove_row) {
                    table.row(row).remove().draw(false);
                } else if (res.updated_html) {
                    table.row(row).remove();
                    $('#harvestsTable tbody').append(res.updated_html);
                    table.draw(false);
                } else {
                    row.find('td').eq(4).html('<span class="inline-block px-2 py-1 rounded bg-green-100 text-green-800 text-xs md:text-sm">Done</span>');
                }

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