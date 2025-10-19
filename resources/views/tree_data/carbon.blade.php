@extends('layouts.app')

@section('title', 'Farm Data')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Carbon Sequestration">
                <div class="text-sm text-black/90 space-y-0.5">
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="tree_select" class="block font-semibold mb-2">Select Measurement (TreeData)</label>
                            <select id="tree_select" class="w-full border rounded px-3 py-2">
                                @foreach($trees as $t)
                                    <option value="{{ $t->id }}"
                                        data-tree-code-id="{{ $t->tree_code_id }}"
                                        data-code="{{ optional($t->treeCode)->code ?? '' }}"
                                        data-dbh-in="{{ $t->dbh ?? '' }}"
                                        data-height-m="{{ $t->height ?? '' }}"
                                        data-age="{{ $t->age ?? '' }}"
                                        data-stem-diameter="{{ $t->stem_diameter ?? '' }}"
                                        data-canopy-diameter="{{ $t->canopy_diameter ?? '' }}"
                                        data-estimated-biomass="{{ $t->estimated_biomass_kg ?? '' }}"
                                        data-carbon-stock="{{ $t->carbon_stock_kg ?? '' }}"
                                        data-annual-sequestration="{{ $t->annual_sequestration_kgco2 ?? '' }}"
                                    >
                                        {{ $t->id }} — {{ optional($t->treeCode)->code ?? 'Uncoded' }} (DBH {{ $t->dbh ?? '—' }} in, H {{ $t->height ?? '—' }} m)
                                    </option>
                                @endforeach
                            </select>

                            <div class="mt-3 flex items-center gap-2">
                                <button id="computeBtn" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">
                                    Compute & Save
                                </button>
                                <button id="computeAllBtn" class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                                    Compute All
                                </button>
                                <button id="refreshBtn" class="bg-gray-200 text-gray-800 px-3 py-2 rounded text-sm hover:bg-gray-300">
                                    Refresh
                                </button>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <div class="bg-gray-100 p-4 rounded">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-sm text-gray-600">DBH (in)</p>
                                        <p class="font-semibold" id="dbhOutput">—</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">DBH (cm)</p>
                                        <p class="font-semibold" id="dbhCmOutput">—</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Height (m)</p>
                                        <p class="font-semibold" id="heightOutput">—</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Age</p>
                                        <p class="font-semibold" id="ageOutput">—</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Estimated Biomass (kg)</p>
                                        <p class="font-semibold text-green-700" id="biomassOutput">—</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Carbon Stock (kg C)</p>
                                        <p class="font-semibold text-blue-700" id="carbonStockOutput">—</p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-sm text-gray-600">Annual Sequestration (kg CO₂ / yr)</p>
                                        <p class="font-semibold text-indigo-700" id="sequestrationOutput">—</p>
                                    </div>
                                </div>

                                <hr class="my-3">

                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-green-700 font-semibold">
                                            Total Annual Sequestration (selected tree_code): <span id="totalByCode">—</span> kg CO₂ / yr
                                        </p>
                                        <p class="text-sm text-blue-700 font-semibold">
                                            Average per measurement (selected code): <span id="avgByCode">—</span> kg CO₂ / yr
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-600">Rows</p>
                                        <p class="font-semibold" id="rowsCount">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <canvas id="carbonChart" height="160" class="mt-6"></canvas>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const selector = document.getElementById('tree_select');
                            const computeBtn = document.getElementById('computeBtn');
                            const computeAllBtn = document.getElementById('computeAllBtn');
                            const refreshBtn = document.getElementById('refreshBtn');

                            const dbhOutput = document.getElementById('dbhOutput');
                            const dbhCmOutput = document.getElementById('dbhCmOutput');
                            const heightOutput = document.getElementById('heightOutput');
                            const ageOutput = document.getElementById('ageOutput');
                            const biomassOutput = document.getElementById('biomassOutput');
                            const carbonStockOutput = document.getElementById('carbonStockOutput');
                            const sequestrationOutput = document.getElementById('sequestrationOutput');

                            const totalByCodeEl = document.getElementById('totalByCode');
                            const avgByCodeEl = document.getElementById('avgByCode');
                            const rowsCountEl = document.getElementById('rowsCount');

                            // Chart.js setup
                            const ctx = document.getElementById('carbonChart').getContext('2d');
                            let chart = new Chart(ctx, {
                                type: 'bar',
                                data: { labels: [], datasets: [{ label: 'kg CO₂ / yr', data: [], backgroundColor: [] }] },
                                options: {
                                    responsive: true,
                                    plugins: { legend: { display: false } },
                                    scales: { y: { beginAtZero: true } }
                                }
                            });

                            // Calculation parameters (adjustable)
                            const DEFAULT_ALPHA = 0.05;
                            const DEFAULT_CARBON_FRACTION = 0.50;
                            const DEFAULT_GROWTH_FRACTION = 0.02;
                            const C_TO_CO2 = 44 / 12;

                            function readOptionData(opt) {
                                return {
                                    id: opt.value,
                                    tree_code_id: opt.dataset.treeCodeId || null,
                                    code: opt.dataset.code || '',
                                    dbh_in: parseFloat(opt.dataset.dbhIn || 0) || 0,
                                    height_m: parseFloat(opt.dataset.heightM || 0) || 0,
                                    age: opt.dataset.age || null,
                                    stem_diameter: opt.dataset.stemDiameter || null,
                                    canopy_diameter: opt.dataset.canopyDiameter || null,
                                    estimated_biomass: opt.dataset.estimatedBiomass || null,
                                    carbon_stock: opt.dataset.carbonStock || null,
                                    annual_sequestration: opt.dataset.annualSequestration || null
                                };
                            }

                            function computeFromData(row, params = {}) {
                                const alpha = typeof params.alpha === 'number' ? params.alpha : DEFAULT_ALPHA;
                                const carbonFraction = typeof params.carbon_fraction === 'number' ? params.carbon_fraction : DEFAULT_CARBON_FRACTION;
                                const growth = typeof params.annual_growth_fraction === 'number' ? params.annual_growth_fraction : DEFAULT_GROWTH_FRACTION;

                                const dbh_in = Number(row.dbh_in || 0);
                                const height_m = Number(row.height_m || 0);
                                const dbh_cm = dbh_in * 2.54;

                                const biomass = alpha * Math.pow(dbh_cm, 2) * height_m; // kg
                                const carbonStock = biomass * carbonFraction; // kg C
                                const annualCgain = carbonStock * growth; // kg C / yr
                                const annualCO2 = annualCgain * C_TO_CO2; // kg CO2 / yr

                                return {
                                    dbh_cm: Number(dbh_cm.toFixed(4)),
                                    estimated_biomass_kg: Number(biomass.toFixed(4)),
                                    carbon_stock_kg: Number(carbonStock.toFixed(4)),
                                    annual_sequestration_kgco2: Number(annualCO2.toFixed(4))
                                };
                            }

                            function updateDetailUI(payload, row) {
                                dbhOutput.textContent = (row.dbh_in || 0).toFixed ? (row.dbh_in).toFixed(2) : String(row.dbh_in);
                                dbhCmOutput.textContent = payload.dbh_cm.toFixed(2);
                                heightOutput.textContent = (row.height_m || 0).toFixed(2);
                                ageOutput.textContent = row.age || '—';
                                biomassOutput.textContent = payload.estimated_biomass_kg.toFixed(2);
                                carbonStockOutput.textContent = payload.carbon_stock_kg.toFixed(2);
                                sequestrationOutput.textContent = payload.annual_sequestration_kgco2.toFixed(2);
                            }

                            function buildChartForCode(tree_code_id) {
                                // gather all options that match tree_code_id
                                const opts = Array.from(selector.options).filter(o => String(o.dataset.treeCodeId || '') === String(tree_code_id));
                                const labels = [];
                                const data = [];
                                const bg = [];

                                opts.forEach(o => {
                                    const row = readOptionData(o);
                                    const comp = computeFromData(row);
                                    labels.push(row.id + (row.code ? ' — ' + row.code : ''));
                                    data.push(comp.annual_sequestration_kgco2);
                                    bg.push('#4CAF50');
                                });

                                chart.data.labels = labels;
                                chart.data.datasets[0].data = data;
                                chart.data.datasets[0].backgroundColor = bg;
                                chart.update();

                                // totals
                                const total = data.reduce((s, v) => s + v, 0);
                                const avg = data.length ? (total / data.length) : 0;
                                totalByCodeEl.textContent = total.toFixed(2);
                                avgByCodeEl.textContent = avg.toFixed(2);
                                rowsCountEl.textContent = String(data.length);
                            }

                            // initial selection
                            function refreshFromSelection() {
                                const opt = selector.options[selector.selectedIndex];
                                if (!opt) return;
                                const row = readOptionData(opt);
                                const payload = computeFromData(row);
                                updateDetailUI(payload, row);
                                buildChartForCode(row.tree_code_id);
                            }

                            selector.addEventListener('change', refreshFromSelection);

                            computeBtn.addEventListener('click', function () {
                                const opt = selector.options[selector.selectedIndex];
                                if (!opt) return alert('Select a measurement first');
                                const id = opt.value;
                                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                                computeBtn.disabled = true;
                                fetch(`/tree_data/${id}/compute-carbon`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({})
                                })
                                .then(r => r.json())
                                .then(json => {
                                    // update option datasets with returned values if present
                                    if (json.payload) {
                                        const p = json.payload;
                                        opt.dataset.estimatedBiomass = p.estimated_biomass_kg;
                                        opt.dataset.carbonStock = p.carbon_stock_kg;
                                        opt.dataset.annualSequestration = p.annual_sequestration_kgco2;
                                    } else {
                                        // API returns the created record
                                        opt.dataset.estimatedBiomass = json.estimated_biomass_kg || opt.dataset.estimatedBiomass;
                                        opt.dataset.carbonStock = json.carbon_stock_kg || opt.dataset.carbonStock;
                                        opt.dataset.annualSequestration = json.annual_sequestration_kgco2 || opt.dataset.annualSequestration;
                                    }
                                    refreshFromSelection();
                                })
                                .catch(err => console.error(err))
                                .finally(() => { computeBtn.disabled = false; });
                            });

                            computeAllBtn.addEventListener('click', function () {
                                if (!confirm('Compute and save carbon metrics for all loaded measurements?')) return;
                                computeAllBtn.disabled = true;
                                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                                fetch(`/tree_data/compute-carbon/bulk`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({})
                                })
                                .then(r => r.json())
                                .then(json => {
                                    alert('Updated ' + (json.updated || 0) + ' rows');
                                    // optional: reload page to refresh datasets
                                    location.reload();
                                })
                                .catch(err => console.error(err))
                                .finally(() => { computeAllBtn.disabled = false; });
                            });

                            refreshBtn.addEventListener('click', function () { location.reload(); });

                            // initialize UI
                            if (selector.options.length > 0) {
                                selector.selectedIndex = 0;
                                refreshFromSelection();
                            }
                        });
                    </script>
                </div>
            </x-card>
        </section>
    </main>
@endsection