<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
{{-- <script src="{{ asset('js/app.js') }}"></script> --}}
@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Analytics')

@section('content')
    <h1 class="font-extrabold text-lg mb-6">
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
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
            </h2>
        </section>

<h1 class="font-extrabold text-lg mb-6">
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Carbon Sequestration Analysis">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <div class="mt-8">
                            <label for="tree_id" class="block font-semibold mb-2">Select Tree</label>
                            <select id="tree_id" class="w-full border rounded px-3 py-2">
                                @foreach ($trees as $tree)
                                    <option value="{{ $tree->id }}"
                                        data-code="{{ $tree->code }}"
                                        data-biomass="{{ $tree->estimated_biomass_kg }}"
                                        data-stock="{{ $tree->carbon_stock_kg }}"
                                        data-sequestration="{{ $tree->annual_sequestration_kg }}">
                                        {{ $tree->code }} (Type: {{ $tree->type ?? 'Unspecified' }})
                                    </option>
                                @endforeach
                            </select>

                            <div class="mt-6 bg-gray-100 p-4 rounded">
                                <p><strong>Estimated Biomass:</strong> <span id="biomassOutput">—</span> kg</p>
                                <p><strong>Carbon Stock:</strong> <span id="carbonStockOutput">—</span> kg</p>
                                <p><strong>Annual Sequestration:</strong> <span id="sequestrationOutput">—</span> kg</p>
                            </div>

                            <canvas id="carbonChart" height="150" class="mt-6"></canvas>

                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector = document.getElementById('tree_id');
            const biomassOutput = document.getElementById('biomassOutput');
            const carbonStockOutput = document.getElementById('carbonStockOutput');
            const sequestrationOutput = document.getElementById('sequestrationOutput');

            const chartData = @json($chartData);
            const labels = chartData.map(t => t.code);
            const data = chartData.map(t => t.sequestration);

            const backgroundColors = labels.map(() => '#4CAF50');

            const chart = new Chart(document.getElementById('carbonChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Annual Carbon Sequestration (kg)',
                        data: data,
                        backgroundColor: backgroundColors,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.parsed.y} kg`
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'kg of CO₂ Sequestered'
                            }
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

            selector.addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const code = selected.dataset.code;

                biomassOutput.textContent = selected.dataset.biomass;
                carbonStockOutput.textContent = selected.dataset.stock;
                sequestrationOutput.textContent = selected.dataset.sequestration;

                highlightBar(code);
            });

            selector.dispatchEvent(new Event('change'));
        });
        </script>

                        </div>

                    </div>
                </x-card>
            </h2>
        </section>
@endsection
