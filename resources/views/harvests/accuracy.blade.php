@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 md:p-6 bg-white">
    <h2 class="text-xl md:text-2xl font-bold mb-4">Prediction Accuracy</h2>

    <div class="w-full overflow-x-auto">
        <canvas id="accuracyChart" height="100"></canvas>
    </div>

    <div class="mt-6">
        <h3 class="text-base md:text-lg font-semibold mb-3">Error Metrics</h3>
        <!-- <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-xs md:text-sm text-gray-600">MAE</p>
                <p class="text-lg md:text-xl font-semibold">{{ $metrics['MAE'] }}</p>
            </div>
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-xs md:text-sm text-gray-600">MSE</p>
                <p class="text-lg md:text-xl font-semibold">{{ $metrics['MSE'] }}</p>
            </div> -->
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-xs md:text-sm text-gray-600">RMSE</p>
                <p class="text-lg md:text-xl font-semibold">{{ $metrics['RMSE'] }}</p>
            </div>
            <div class="bg-gray-50 p-3 rounded">
                <p class="text-xs md:text-sm text-gray-600">MAPE</p>
                <p class="text-lg md:text-xl font-semibold">{{ $metrics['MAPE'] }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('accuracyChart').getContext('2d');
    let chart;

    function getChartConfig() {
        const isMobile = window.innerWidth < 768;
        const chartType = isMobile ? 'bar' : 'line';

        return {
            type: chartType,
            data: {
                labels: {!! json_encode($labels) !!},
                datasets: [
                    {
                        label: 'Actual Harvest',
                        data: {!! json_encode($actual) !!},
                        borderColor: 'rgba(34,197,94,1)',
                        backgroundColor: isMobile ? 'rgba(34,197,94,0.8)' : 'rgba(34,197,94,0.2)',
                        borderWidth: isMobile ? 0 : 2,
                        fill: !isMobile,
                        tension: 0.3,
                        borderRadius: isMobile ? 4 : 0
                    },
                    {
                        label: 'Predicted Harvest',
                        data: {!! json_encode($predicted) !!},
                        borderColor: 'rgba(59,130,246,1)',
                        backgroundColor: isMobile ? 'rgba(59,130,246,0.8)' : 'rgba(59,130,246,0.2)',
                        borderWidth: isMobile ? 0 : 2,
                        fill: !isMobile,
                        tension: 0.3,
                        borderRadius: isMobile ? 4 : 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    tooltip: { mode: 'index', intersect: false },
                    legend: { position: 'top', labels: { font: { size: isMobile ? 12 : 14 } } }
                },
                interaction: { mode: 'nearest', axis: 'x', intersect: false },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Harvest Weight (kg)', font: { size: isMobile ? 11 : 12 } },
                        ticks: { font: { size: isMobile ? 10 : 12 } }
                    },
                    x: {
                        title: { display: true, text: 'Month', font: { size: isMobile ? 11 : 12 } },
                        ticks: { font: { size: isMobile ? 10 : 12 } }
                    }
                }
            }
        };
    }

    function initChart() {
        if (chart) {
            chart.destroy();
        }
        chart = new Chart(ctx, getChartConfig());
    }

    initChart();

    window.addEventListener('resize', () => {
        initChart();
    });
</script>
@endsection