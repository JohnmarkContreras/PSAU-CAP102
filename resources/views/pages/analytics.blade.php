@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
    {{-- Load Chart.js once --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Analytics Chart --}}
    <section class="bg-[rgb(233,238,233)] rounded-lg p-4 relative">
        <x-card title="Analytics">
            <div class="text-sm text-black/90 space-y-0.5">
                <canvas id="myChart"></canvas>
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const ctx = document.getElementById('myChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Sweet', 'Semi-Sweet', 'Sour'],
                                datasets: [{
                                    label: 'Tamarind Flavor Count',
                                    data: [{{ $sweet }}, {{ $semi }}, {{ $sour }}],
                                    backgroundColor: ['#86efac', '#fde68a', '#fca5a5']
                                }]
                            },
                            options: {
                                responsive: true,
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    });
                </script>
            </div>
        </x-card>
    </section>

{{-- Carbon Sequestration Analysis (DBH + Height only) --}}
<section class="bg-[#e9eee9] rounded-lg p-4 relative">
<x-card title="Carbon Sequestration Analysis">
    <div class="text-sm text-black/90 space-y-0.5">
    <div class="mt-8">
        <label for="tree_id" class="block font-semibold mb-2">Select Tree</label>
        <select id="tree_id" class="w-full border rounded px-3 py-2">
        @foreach ($trees as $tree)
            <option value="{{ $tree->id }}"
                    data-code="{{ $tree->code }}"
                    data-dbh="{{ $tree->dbh_cm ?? '' }}"
                    data-height="{{ $tree->height_m ?? '' }}"
                    data-growth="{{ $tree->annual_growth_fraction ?? '' }}">
            {{ $tree->code }} (DBH: {{ $tree->dbh_cm ?? '—' }} cm, H: {{ $tree->height_m ?? '—' }} m)
            </option>
        @endforeach
        </select>

        <div class="mt-6 bg-gray-100 p-4 rounded">
        <p><strong>DBH:</strong> <span id="dbhOutput">—</span> cm</p>
        <p><strong>Height:</strong> <span id="heightOutput">—</span> m</p>
        <p><strong>Estimated Biomass:</strong> <span id="biomassOutput">—</span> kg</p>
        <p><strong>Carbon Stock (C):</strong> <span id="carbonStockOutput">—</span> kg</p>
        <p><strong>Annual Sequestration:</strong> <span id="sequestrationOutput">—</span> kg CO₂ / yr</p>
        <hr class="my-3">
        <p class="text-green-700 font-semibold">
            Total Annual Sequestration (All Trees): <span id="totalSequestration">—</span> kg CO₂ / yr
        </p>
        <p class="text-blue-700 font-semibold">
            Average Annual Sequestration (Per Tree): <span id="avgSequestration">—</span> kg CO₂ / yr
        </p>
        </div>

        <canvas id="carbonChart" height="150" class="mt-6"></canvas>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
        // Single formula flow:
        // Biomass (kg) = ALPHA * (DBH_cm^2 * Height_m)
        // Carbon stock (kg C) = Biomass * CARBON_FRACTION
        // Annual CO2 sequestration (kg CO2/yr) = (Carbon stock * ANNUAL_GROWTH_FRACTION) * (44/12)
        const ALPHA = 0.05;               // empirical coefficient (adjustable for species)
        const CARBON_FRACTION = 0.50;     // fraction of dry biomass that is carbon
        const DEFAULT_ANNUAL_GROWTH = 0.02; // default 2% carbon stock increase per year
        const C_TO_CO2 = 44 / 12;

        const selector = document.getElementById('tree_id');
        const dbhOutput = document.getElementById('dbhOutput');
        const heightOutput = document.getElementById('heightOutput');
        const biomassOutput = document.getElementById('biomassOutput');
        const carbonStockOutput = document.getElementById('carbonStockOutput');
        const sequestrationOutput = document.getElementById('sequestrationOutput');
        const totalSequestrationOutput = document.getElementById('totalSequestration');
        const avgSequestrationOutput = document.getElementById('avgSequestration');

        // Build data from select options (reads DBH and Height only)
        const options = Array.from(selector.options);
        const chartData = options.map(opt => {
            const code = opt.dataset.code || opt.textContent.trim();
            const dbh = parseFloat(opt.dataset.dbh || 0) || 0;      // cm
            const height = parseFloat(opt.dataset.height || 0) || 0; // m
            const growth = parseFloat(opt.dataset.growth || DEFAULT_ANNUAL_GROWTH) || DEFAULT_ANNUAL_GROWTH;

            // single needed formula: biomass from DBH and Height
            const biomass = ALPHA * Math.pow(dbh, 2) * height; // kg
            const carbonStock = biomass * CARBON_FRACTION;     // kg C
            const annualCgain = carbonStock * growth;          // kg C / yr
            const annualCO2 = annualCgain * C_TO_CO2;          // kg CO2 / yr

            return { code, dbh, height, growth, biomass, carbonStock, annualCO2 };
        });

        const labels = chartData.map(t => t.code);
        const sequestrationData = chartData.map(t => Number(t.annualCO2.toFixed(2)));

        const totalSequestration = sequestrationData.reduce((sum, v) => sum + v, 0);
        const avgSequestration = chartData.length ? totalSequestration / chartData.length : 0;

        totalSequestrationOutput.textContent = totalSequestration.toFixed(2);
        avgSequestrationOutput.textContent = avgSequestration.toFixed(2);

        // Chart.js bar chart
        const ctx = document.getElementById('carbonChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
            labels: labels,
            datasets: [{
                label: 'Annual Carbon Sequestration (kg CO₂ / yr)',
                data: sequestrationData,
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
                    label: function(ctx) { return `${ctx.parsed.y} kg CO₂ / yr`; }
                }
                }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'kg CO₂ / yr' } }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                const index = elements[0].index;
                const selected = chartData[index];
                updateOutputs(selected);
                // sync selector
                for (let i = 0; i < selector.options.length; i++) {
                    if ((selector.options[i].dataset.code || '').trim() === selected.code) {
                    selector.selectedIndex = i; break;
                    }
                }
                highlightBar(selected.code);
                }
            }
            }
        });

        function highlightBar(code) {
            chart.data.datasets[0].backgroundColor = labels.map(label =>
            label === code ? '#FF5722' : '#4CAF50'
            );
            chart.update();
        }

        function updateOutputs(item) {
            dbhOutput.textContent = Number(item.dbh).toFixed(2);
            heightOutput.textContent = Number(item.height).toFixed(2);
            biomassOutput.textContent = Number(item.biomass).toFixed(2);
            carbonStockOutput.textContent = Number(item.carbonStock).toFixed(2);
            sequestrationOutput.textContent = Number(item.annualCO2).toFixed(2);
        }

        selector.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            const code = opt.dataset.code || opt.textContent.trim();
            const found = chartData.find(t => t.code === code);
            if (found) updateOutputs(found);
            highlightBar(code);
        });

        if (chartData.length > 0) {
            selector.selectedIndex = 0;
            updateOutputs(chartData[0]);
            highlightBar(chartData[0].code);
        }
        });
        </script>
    </div>
    </div>
</x-card>
</section>
@endsection
