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
                        @php
                            // Calculate accuracy percentage (100% - MAPE)
                            $accuracy = $bt['mape'] !== null ? max(0, 100 - $bt['mape']) : null;
                            
                            // Determine accuracy level and styling
                            $accuracyLevel = '';
                            $accuracyColor = '';
                            $accuracyBgColor = '';
                            $accuracyIcon = '';
                            
                            if ($accuracy !== null) {
                                if ($accuracy >= 90) {
                                    $accuracyLevel = 'Excellent';
                                    $accuracyColor = 'text-green-700';
                                    $accuracyBgColor = 'bg-green-50';
                                    $accuracyIcon = '🎯';
                                } elseif ($accuracy >= 80) {
                                    $accuracyLevel = 'Very Good';
                                    $accuracyColor = 'text-blue-700';
                                    $accuracyBgColor = 'bg-blue-50';
                                    $accuracyIcon = '✓';
                                } elseif ($accuracy >= 70) {
                                    $accuracyLevel = 'Good';
                                    $accuracyColor = 'text-teal-700';
                                    $accuracyBgColor = 'bg-teal-50';
                                    $accuracyIcon = '👍';
                                } elseif ($accuracy >= 60) {
                                    $accuracyLevel = 'Fair';
                                    $accuracyColor = 'text-yellow-700';
                                    $accuracyBgColor = 'bg-yellow-50';
                                    $accuracyIcon = '⚠️';
                                } else {
                                    $accuracyLevel = 'Needs Improvement';
                                    $accuracyColor = 'text-orange-700';
                                    $accuracyBgColor = 'bg-orange-50';
                                    $accuracyIcon = '⚡';
                                }
                            }
                        @endphp
                        
                        <div class="mb-10 border-t pt-6">
                            <h3 class="text-lg font-bold mb-4">
                                Backtest Cutoff: {{ $bt['cutoff'] }}
                            </h3>

                            {{-- User-Friendly Accuracy Display --}}
                            @if($accuracy !== null)
                                <div class="mb-6 {{ $accuracyBgColor }} border-2 border-{{ explode('-', $accuracyColor)[1] }}-200 rounded-xl p-6">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <div class="flex items-center gap-4">
                                            <span class="text-4xl">{{ $accuracyIcon }}</span>
                                            <div>
                                                <p class="text-sm text-gray-600 mb-1">Forecast Accuracy</p>
                                                <p class="text-3xl font-bold {{ $accuracyColor }}">
                                                    {{ number_format($accuracy, 1) }}%
                                                </p>
                                                <p class="text-sm font-medium {{ $accuracyColor }} mt-1">
                                                    {{ $accuracyLevel }}
                                                </p>
                                            </div>
                                        </div>
                                        
                                        {{-- Visual Progress Bar --}}
                                        <div class="flex-1 min-w-[200px] max-w-md">
                                            <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2
                                                    {{ $accuracy >= 90 ? 'bg-green-600' : '' }}
                                                    {{ $accuracy >= 80 && $accuracy < 90 ? 'bg-blue-600' : '' }}
                                                    {{ $accuracy >= 70 && $accuracy < 80 ? 'bg-teal-600' : '' }}
                                                    {{ $accuracy >= 60 && $accuracy < 70 ? 'bg-yellow-600' : '' }}
                                                    {{ $accuracy < 60 ? 'bg-orange-600' : '' }}"
                                                    style="width: {{ $accuracy }}%">
                                                    <span class="text-white text-xs font-bold">{{ number_format($accuracy, 1) }}%</span>
                                                </div>
                                            </div>
                                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                                <span>0%</span>
                                                <span>50%</span>
                                                <span>100%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Explanation --}}
                                    <div class="mt-4 pt-4 border-t border-{{ explode('-', $accuracyColor)[1] }}-200">
                                        <p class="text-sm text-gray-700">
                                            <strong>What this means:</strong> 
                                            @if($accuracy >= 90)
                                                Our forecast is highly accurate! Predictions are typically within 10% of actual harvest amounts.
                                            @elseif($accuracy >= 80)
                                                Our forecast is very reliable. Predictions are typically within 20% of actual harvest amounts.
                                            @elseif($accuracy >= 70)
                                                Our forecast provides good guidance. Predictions are typically within 30% of actual harvest amounts.
                                            @elseif($accuracy >= 60)
                                                Our forecast gives a fair estimate. Consider adding a margin of ±40% for planning.
                                            @else
                                                Our forecast provides a general estimate. Predictions may vary significantly from actual results.
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif

                            {{-- Technical Metrics (Collapsible) --}}
                            <details class="mb-6">
                                <summary class="cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-800 mb-2">
                                    📊 Show Technical Metrics
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div class="p-4 bg-green-100 rounded-lg text-center">
                                        <p class="text-sm text-gray-600">MAPE (non-zero)</p>
                                        <p class="text-2xl font-semibold text-green-800">
                                            {{ $bt['mape'] !== null ? number_format($bt['mape'], 2).'%' : 'N/A' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">Mean Absolute Percentage Error</p>
                                    </div>
                                    <div class="p-4 bg-blue-100 rounded-lg text-center">
                                        <p class="text-sm text-gray-600">RMSE (non-zero)</p>
                                        <p class="text-2xl font-semibold text-blue-800">
                                            {{ $bt['rmse'] !== null ? number_format($bt['rmse'], 2).' kg' : 'N/A' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">Root Mean Square Error</p>
                                    </div>
                                </div>
                            </details>

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