@extends('layouts.app')

@section('title', 'Farm Data')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
    <x-card title="Harvest Analytics">
        <form method="GET" id="harvestFilterForm" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
            {{-- Tree Type --}}
            <select name="type" id="typeSelect" class="border rounded p-2">
                <option value="">All Types</option>
                <option value="SOUR" {{ request('type') === 'SOUR' ? 'selected' : '' }}>SOUR</option>
                <option value="SWEET" {{ request('type') === 'SWEET' ? 'selected' : '' }}>SWEET</option>
                <option value="SEMI_SWEET" {{ request('type') === 'SEMI_SWEET' ? 'selected' : '' }}>SEMI-SWEET</option>
            </select>

            {{-- DBH Range --}}
            <input type="number" step="0.1" name="min_dbh" id="min_dbh" value="{{ request('min_dbh') }}" placeholder="Min DBH (cm)" class="border rounded p-2">
            <input type="number" step="0.1" name="max_dbh" id="max_dbh" value="{{ request('max_dbh') }}" placeholder="Max DBH (cm)" class="border rounded p-2">

            {{-- Height Range --}}
            <input type="number" step="0.1" name="min_height" id="min_height" value="{{ request('min_height') }}" placeholder="Min Height (m)" class="border rounded p-2">
            <input type="number" step="0.1" name="max_height" id="max_height" value="{{ request('max_height') }}" placeholder="Max Height (m)" class="border rounded p-2">

            {{-- Buttons (Filter + Reset) --}}
            <div class="flex gap-2">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Filter
                </button>
                <button type="button" id="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Reset
                </button>
            </div>
        </form>

        {{-- Bar Chart --}}
        <canvas id="harvestChart" height="160"></canvas>
    </x-card>
</section>


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

{{-- HARVEST BAR CHART & FILTERS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const harvestData = @json($harvestData);
    const ctx = document.getElementById('harvestChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: harvestData.map(t => t.code),
            datasets: [{
                label: 'Harvest Weight (kg)',
                data: harvestData.map(t => t.total_kg),
                backgroundColor: '#4CAF50',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Total Harvest (kg)' } }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} kg`
                    }
                }
            }
        }
    });
});
</script>
{{-- === Script for Reset + Auto-Clear === --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('harvestFilterForm');
    const typeSelect = document.getElementById('typeSelect');
    const resetBtn = document.getElementById('resetFilters');

    // 🔹 When the type changes → clear numeric fields & submit form
    typeSelect.addEventListener('change', () => {
        document.getElementById('min_dbh').value = '';
        document.getElementById('max_dbh').value = '';
        document.getElementById('min_height').value = '';
        document.getElementById('max_height').value = '';
        form.submit();
    });

    // 🔹 When "Reset" button clicked → clear all & submit (show all records)
    resetBtn.addEventListener('click', () => {
        form.querySelectorAll('input, select').forEach(el => el.value = '');
        form.submit();
    });
});
</script>
@endsection
