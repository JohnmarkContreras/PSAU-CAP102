@extends('layouts.app')

@section('title', 'Harvest forecasting')

@section('content')
<main id="Harvest forecast" class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Harvest Forecast">
            <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-6">
                {{-- Filter by code --}}
                <form method="GET" action="{{ route('harvest.backtest') }}" class="mb-4">
                    <label class="mr-2">Tree Code:</label>
                    <select name="code" class="border rounded p-1">
                        <option value="">-- All Codes --</option>
                        @foreach($codes as $code)
                            <option value="{{ $code }}" {{ $selectedCode == $code ? 'selected' : '' }}>
                                {{ $code }}
                            </option>
                        @endforeach
                    </select>
                    <button class="bg-blue-600 text-white px-3 py-1 rounded">Filter</button>
                </form>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="p-4 bg-green-100 rounded-lg">
                        <p class="text-sm text-gray-600">MAPE (non-zero)</p>
                        <p class="text-2xl font-semibold text-green-800">
                            {{ $mape ? number_format($mape, 2) . '%' : 'N/A' }}
                        </p>
                    </div>
                    <div class="p-4 bg-blue-100 rounded-lg">
                        <p class="text-sm text-gray-600">RMSE (non-zero)</p>
                        <p class="text-2xl font-semibold text-blue-800">
                            {{ $rmse ? number_format($rmse, 2) . ' kg' : 'N/A' }}
                        </p>
                    </div>
                </div>

                @if(!empty($dates) && !empty($actual))
                <div class="mt-6">
                    <h3 class="text-lg font-bold mb-2">Backtest Records</h3>
                        <table id="backtestTable" class="min-w-full border border-gray-300 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-2 py-1">Month</th>
                                    <th class="border px-2 py-1">Actual (kg)</th>
                                    <th class="border px-2 py-1">Predicted (kg)</th>
                                    <th class="border px-2 py-1">Error (kg)</th>
                                    <th class="border px-2 py-1">Error (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dates as $i => $date)
                                    @php
                                        $act = $actual[$i] ?? null;
                                        $pred = $predicted[$i] ?? null;
                                        $error = ($act !== null && $pred !== null) ? $act - $pred : null;
                                        $errorPct = ($act && $pred !== null && $act != 0)
                                            ? (($act - $pred) / $act) * 100
                                            : null;
                                    @endphp
                                    <tr>
                                        <td class="border px-2 py-1">{{ $date }}</td>
                                        <td class="border px-2 py-1">{{ $act !== null ? number_format($act, 2) : '—' }}</td>
                                        <td class="border px-2 py-1">{{ $pred !== null ? number_format($pred, 2) : '—' }}</td>
                                        <td class="border px-2 py-1 {{ $error > 0 ? 'text-green-700' : ($error < 0 ? 'text-red-700' : '') }}">
                                            {{ $error !== null ? number_format($error, 2) : '—' }}
                                        </td>
                                        <td class="border px-2 py-1">
                                            {{ $errorPct !== null ? number_format($errorPct, 2).'%' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                </div>
            @endif
        </x-card>
    </section>
</main>
    <canvas id="backtestChart" height="120"></canvas>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    $('#backtestTable').DataTable({
        pageLength: 10,   // show 10 rows by default
        lengthMenu: [5, 10, 25, 50],
        ordering: false   // disable column sorting if you want
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('backtestChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($dates), // e.g. ["2020-01","2021-01","2022-01",...]
            datasets: [
                {
                    label: 'Estimated Harvest (kg/tree)',
                    data: @json($actual), // your actual/estimated values
                    borderColor: 'rgba(255, 159, 64, 1)', // orange
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    borderWidth: 2,
                    pointStyle: 'circle',
                    pointRadius: 4,
                    tension: 0.3
                },
                {
                    label: 'Linear Regression Fit',
                    data: @json($predicted), // your fitted/trend values
                    borderColor: 'rgba(54, 162, 235, 1)', // blue
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderDash: [6, 6], // dashed line
                    borderWidth: 2,
                    tension: 0.3
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: {
                    display: true,
                    text: 'Tamarind Tree Harvest (2020–2025) Estimated vs. Trend'
                }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Harvest (kg/tree)' }
                },
                x: {
                    title: { display: true, text: 'Year' }
                }
            }
        }
    });
});
</script>
@endpush
