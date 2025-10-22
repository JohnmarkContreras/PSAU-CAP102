@extends('layouts.app')

@section('title', 'Harvest forecasting')

@section('content')
<main id="Harvest forecast" class="flex-1 p-4 sm:p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Harvest Forecast">
            <div class="max-w-5xl mx-auto bg-white shadow rounded-lg p-4 sm:p-6">
                
                {{-- Filter by code --}}
                <form method="GET" action="{{ route('harvest.backtest') }}" 
                      class="mb-4 flex flex-col sm:flex-row sm:items-center sm:space-x-2">
                    <label class="text-sm font-medium mb-1 sm:mb-0">Tree Code:</label>
                    <select name="code" class="border rounded p-2 min-w-60 sm:w-auto">
                        <option value="">-- All Codes --</option>
                        @foreach($codes as $code)
                            <option value="{{ $code }}" {{ $selectedCode == $code ? 'selected' : '' }}>
                                {{ $code }}
                            </option>
                        @endforeach
                    </select>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded ml-2 sm:mt-0 min-w-60 sm:w-auto">
                        Filter
                    </button>
                </form>

                {{-- Backtests --}}
                @if(!empty($backtests))
                    @foreach($backtests as $i => $bt)
                        <div class="mb-10 border-t pt-6">
                            <h3 class="text-lg font-bold mb-4">
                                Backtest Cutoff: {{ $bt['cutoff'] }}
                            </h3>

                            {{-- Metrics --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="p-4 bg-green-100 rounded-lg text-center">
                                    <p class="text-sm text-gray-600">MAPE (non-zero)</p>
                                    <p class="text-2xl font-semibold text-green-800">
                                        {{ $bt['mape'] !== null ? number_format($bt['mape'], 2).'%' : 'N/A' }}
                                    </p>
                                </div>
                                <div class="p-4 bg-blue-100 rounded-lg text-center">
                                    <p class="text-sm text-gray-600">RMSE (non-zero)</p>
                                    <p class="text-2xl font-semibold text-blue-800">
                                        {{ $bt['rmse'] !== null ? number_format($bt['rmse'], 2).' kg' : 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Backtest Records --}}
                            @if(!empty($bt['dates']) && !empty($bt['actual']))
                                <div class="mt-6">
                                    <h4 class="text-md font-bold mb-2">Backtest Records</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border border-gray-300 text-xs sm:text-sm backtest-table">
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
                                                @foreach($bt['dates'] as $j => $date)
                                                    @php
                                                        $act = $bt['actual'][$j] ?? null;
                                                        $pred = $bt['predicted'][$j] ?? null;
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
                                </div>
                            @endif

                            {{-- Chart --}}
                            <div class="mt-8">
                                <canvas id="chart-{{ $i }}" class="w-full h-64 sm:h-96"></canvas>
                            </div>
                        </div>
                    @endforeach
                @else
                    <p>No backtest results available.</p>
                @endif
            </div>
        </x-card>
    </section>
</main>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize DataTables for all backtest tables
    $('.backtest-table').DataTable({
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: false
    });

    // Render charts for each backtest
    @if(!empty($backtests))
        @foreach($backtests as $i => $bt)
            new Chart(document.getElementById("chart-{{ $i }}").getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($bt['dates']),
                    datasets: [
                        {
                            label: 'Actual',
                            data: @json($bt['actual']),
                            borderColor: 'rgba(255, 159, 64, 1)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            borderWidth: 2,
                            pointStyle: 'circle',
                            pointRadius: 4,
                            tension: 0.3
                        },
                        {
                            label: 'Predicted',
                            data: @json($bt['predicted']),
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderDash: [6, 6],
                            borderWidth: 2,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        title: {
                            display: true,
                            text: 'Harvest Forecast Backtest (Cutoff {{ $bt['cutoff'] }})'
                        }
                    },
                    scales: {
                        y: { title: { display: true, text: 'Harvest (kg/tree)' } },
                        x: { title: { display: true, text: 'Year' } }
                    }
                }
            });
        @endforeach
    @endif
});
</script>
@endpush
