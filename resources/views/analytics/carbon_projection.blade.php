@extends('layouts.app')

@section('content')
<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Tamarind Carbon Sequestration Projection</h2>

    <div class="flex items-center gap-4 mb-4">
        <label for="years" class="font-semibold">Projection years:</label>
        <select id="years" class="border rounded p-2">
            <option value="5">5 years</option>
            <option value="10" selected>10 years</option>
            <option value="15">15 years</option>
            <option value="20">20 years</option>
        </select>
    </div>

    <canvas id="projectionChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('projectionChart');
    const chart = new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [{ label: 'Annual CO₂ (kg)', data: [], borderColor: '#16a34a', fill: false, tension: 0.3 }] },
        options: { scales: { y: { beginAtZero: true } } }
    });

    async function loadProjection(years = 10) {
        const response = await fetch(`/tree-data/{{ $tree->id }}/projection?years=${years}`);
        const data = await response.json();
        const labels = Object.keys(data.projection);
        const values = Object.values(data.projection);

        chart.data.labels = labels;
        chart.data.datasets[0].data = values;
        chart.update();
    }

    document.getElementById('years').addEventListener('change', (e) => {
        loadProjection(e.target.value);
    });

    // initial load
    loadProjection();
</script>
@endsection
