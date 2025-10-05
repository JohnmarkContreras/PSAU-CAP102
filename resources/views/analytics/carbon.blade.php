@extends('layouts.app')

@section('title', 'Farm Data')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Carbon Sequestration Analytics">
            <div class="text-sm text-black/90 space-y-0.5">
                {{-- Existing total analytics --}}
                <div class="mt-4">
                    <canvas id="carbonAnalyticsChart" height="160"></canvas>
                </div>
                <div class="mt-4">
                    <p class="text-gray-700">Total computed trees: <strong id="totalCount">0</strong></p>
                    <p class="text-gray-700">Total annual sequestration (kg CO₂/yr): <strong id="totalSum">0</strong></p>
                </div>

                {{-- 🔽 New: Projection Section --}}
                <div class="mt-8">
                    <div class="flex items-center gap-3 mb-3">
                        <label class="font-semibold text-gray-700">Projection Range:</label>
                        <select id="projectionYears" class="border rounded p-1.5">
                            <option value="5" selected>5 years</option>
                            <option value="10">10 years</option>
                            <option value="15">15 years</option>
                            <option value="20">20 years</option>
                        </select>
                    </div>

                    <canvas id="projectionChart" height="140"></canvas>
                </div>
            </div>
        </x-card>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    // === EXISTING BAR CHART ===
    const chartData = @json($chartData);
    const labels = chartData.map(c => c.label);
    const data = chartData.map(c => c.sequestration);
    const total = data.reduce((s, v) => s + v, 0);
    document.getElementById('totalCount').textContent = chartData.length;
    document.getElementById('totalSum').textContent = total.toFixed(2);

    const ctx = document.getElementById('carbonAnalyticsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Annual CO₂ sequestration (kg / yr)',
                data: data,
                backgroundColor: labels.map(() => '#4CAF50'),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} kg CO₂ / yr`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'kg CO₂ / yr' } }
            }
        }
    });

    // === NEW PROJECTION CHART ===
    const projCtx = document.getElementById('projectionChart').getContext('2d');
    const projChart = new Chart(projCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Projected CO₂ Sequestration (kg / yr)',
                data: [],
                borderColor: '#16a34a',
                fill: false,
                tension: 0.3
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'kg CO₂ / yr' } } }
        }
    });

    // === FIXED: Make loadProjection work dynamically ===
    async function loadProjection(years) {
        try {
            const res = await fetch(`{{ url('/analytics/projection') }}?years=${years}`);
            const json = await res.json();

            const yearMap = {};
            json.data.forEach(tree => {
                tree.projection.forEach(p => {
                    if (!yearMap[p.year]) yearMap[p.year] = 0;
                    yearMap[p.year] += p.sequestration;
                });
            });

            projChart.data.labels = Object.keys(yearMap);
            projChart.data.datasets[0].data = Object.values(yearMap);
            projChart.update();
        } catch (err) {
            console.error('Projection load failed:', err);
        }
    }

    //default 10 years)
    await loadProjection(5);

    //When dropdown changes, reload with new years
    document.getElementById('projectionYears').addEventListener('change', async (e) => {
        const years = e.target.value;
        await loadProjection(years);
    });
});
</script>


@endsection
