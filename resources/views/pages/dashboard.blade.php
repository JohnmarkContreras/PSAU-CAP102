@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Dashboard')

@section('content')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('treePieChart');
        if (!canvas) return;

        const labels = @json(['Sour', 'Sweet', 'Semi-Sweet']);
        const data = @json([ $totalsour ?? 0, $totalsweet ?? 0, $totalsemi_sweet ?? 0 ]);
        const colors = ['#7BD666', '#EE918C', '#4F302E']; // Green, pink, Brown

        const total = data.reduce((a, b) => a + b, 0);
        if (total === 0) {
            canvas.parentElement.innerHTML = '<div class="text-center text-gray-500 py-8">No tamarind data available</div>';
            document.getElementById('chartLegend').innerHTML = '';
            return;
        }

        const ctx = canvas.getContext('2d');

        // Destroy old chart if exists
        if (canvas._chartInstance) {
            canvas._chartInstance.destroy();
        }

        // Build chart
        const chart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }, // we use custom legend
                    tooltip: { enabled: true }
                }
            }
        });

        canvas._chartInstance = chart;

        // Build custom legend
        const legendContainer = document.getElementById('chartLegend');
        legendContainer.innerHTML = ''; 

        labels.forEach((label, i) => {
            const value = data[i];
            const percent = ((value / total) * 100);
            const percentStr = (percent >= 1) ? percent.toFixed(1) + '%' : (percent > 0 ? '<1%' : '0%');

            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 bg-white/90 rounded-lg px-3 py-2 shadow-sm';

            const swatch = document.createElement('span');
            swatch.className = 'w-4 h-4 rounded-sm inline-block';
            swatch.style.backgroundColor = colors[i];

            const text = document.createElement('span');
            text.innerHTML = `<strong>${label}</strong>: ${value} trees <span class="text-gray-500">(${percentStr})</span>`;

            row.appendChild(swatch);
            row.appendChild(text);
            legendContainer.appendChild(row);
        });
    });
    </script>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    // Harvest data JSON from server (safe conversion)
    const RAW_HARVESTS = @json($harvests->toArray());
    const listEl = document.getElementById('harvestList');
    const filterForm = document.getElementById('harvestFilterForm');
    const toggleBtn = document.getElementById('toggleSortBtn');

    let sortDesc = true; // default: newest first

    // Robust date parser for common Laravel formats
    function parseDate(s) {
        if (!s) return null;
        // Normalize: convert "YYYY-MM-DD HH:MM:SS" to "YYYY-MM-DDTHH:MM:SS"
        let iso = String(s).replace(' ', 'T');
        // If there's no time part, Date will parse "YYYY-MM-DD" fine.
        const d = new Date(iso);
        if (!isNaN(d)) return d;
        // Last resort: try Date.parse
        const solid = Date.parse(String(s));
        return isNaN(solid) ? null : new Date(solid);
    }

    // Try multiple possible tree code locations
    function getTreeCode(h) {
        if (!h) return 'N/A';
        if (h.tree && (h.tree.code || h.tree.code === 0 || h.tree.code === '')) return h.tree.code;
        if (h.tree_code) return h.tree_code;
        if (h.treeId) return h.treeId;
        if (h.tree_id) return h.tree_id;
        if (h.treeCode) return h.treeCode;
        return 'N/A';
    }

    function formatDate(d) {
        if (!d) return '-';
        return new Intl.DateTimeFormat('en-US', { year: 'numeric', month: 'short', day: '2-digit' }).format(d);
    }

    // Sort and group then return groups object { treeCode: [records...] }
    function groupAndSort(records) {
        // clone array so we don'solid mutate original
        const arr = records.slice();
        arr.sort((a, b) => {
            const da = parseDate(a.harvest_date);
            const db = parseDate(b.harvest_date);
            if (!da && !db) return 0;
            if (!da) return 1;
            if (!db) return -1;
            return sortDesc ? (db - da) : (da - db);
        });

        const groups = {};
        arr.forEach(h => {
            const key = String(getTreeCode(h) ?? 'N/A');
            if (!groups[key]) groups[key] = [];
            groups[key].push(h);
        });
        return groups;
    }

    // Render grouped tables
    function render(records) {
        const groups = groupAndSort(records);

        if (Object.keys(groups).length === 0) {
            listEl.innerHTML = '<p class="text-gray-500 py-6 text-center">No harvest records found.</p>';
            return;
        }

        let html = '';
        for (const [treeCode, recs] of Object.entries(groups)) {
            html += `
                <div class="mb-6">
                    <h3 class="font-bold text-lg text-green-700 mb-2">Tree Code: ${escapeHtml(treeCode)}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border border-gray-200 rounded-lg">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 border">Harvest Date</th>
                                    <th class="px-4 py-2 border">Weight (kg)</th>
                                    <th class="px-4 py-2 border">Quality</th>
                                    <th class="px-4 py-2 border">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            for (const r of recs) {
                const d = parseDate(r.harvest_date);
                const dateStr = formatDate(d);
                const weight = (r.harvest_weight_kg !== undefined && r.harvest_weight_kg !== null) ? r.harvest_weight_kg : '-';
                const quality = r.quality ?? '-';
                const notes = (r.notes !== undefined && r.notes !== null && String(r.notes).trim() !== '') ? escapeHtml(r.notes) : '-';

                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 border">${escapeHtml(dateStr)}</td>
                        <td class="px-4 py-2 border">${escapeHtml(String(weight))}</td>
                        <td class="px-4 py-2 border">${escapeHtml(String(quality))}</td>
                        <td class="px-4 py-2 border">${notes}</td>
                    </tr>
                `;
            }

            html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        listEl.innerHTML = html;
    }

    // Simple HTML escaper
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Initial render with all data (or server-filtered $harvests if provided)
    render(RAW_HARVESTS);

    // Client-side filter: year/month (prevents reload)
    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const yearVal = this.querySelector('select[name="year"]').value;
        const monthVal = this.querySelector('select[name="month"]').value;

        let filtered = RAW_HARVESTS.slice();

        if (yearVal) {
            filtered = filtered.filter(h => {
                const d = parseDate(h.harvest_date);
                return d && d.getFullYear() === Number(yearVal);
            });
        }
        if (monthVal) {
            filtered = filtered.filter(h => {
                const d = parseDate(h.harvest_date);
                return d && (d.getMonth() + 1) === Number(monthVal);
            });
        }

        render(filtered);
    });

    // Toggle sort (Newest / Oldest)
    toggleBtn.addEventListener('click', function () {
        sortDesc = !sortDesc;
        this.textContent = sortDesc ? 'Sort: Newest' : 'Sort: Oldest';

        // Reapply current filters
        const yearVal = filterForm.querySelector('select[name="year"]').value;
        const monthVal = filterForm.querySelector('select[name="month"]').value;
        let filtered = RAW_HARVESTS.slice();

        if (yearVal) {
            filtered = filtered.filter(h => {
                const d = parseDate(h.harvest_date);
                return d && d.getFullYear() === Number(yearVal);
            });
        }
        if (monthVal) {
            filtered = filtered.filter(h => {
                const d = parseDate(h.harvest_date);
                return d && (d.getMonth() + 1) === Number(monthVal);
            });
        }

        render(filtered);
    });
});
</script>
<main id="dashboard-container" class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Dashboard">
            <div class="text-sm text-black/90 space-y-0.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-6 w-full relative">
                    <!-- Total Trees Card -->
                    <div class="bg-[#1F7D53] text-white rounded-md shadow-md overflow-hidden w-full h-30">
                        <!-- Main content -->
                        <div class="p-4 flex flex-col items-center justify-center text-center">
                            <div class="flex items-center justify-center mb-2">
                                <div class="text-center">
                                        <div class="text-2xl font-bold">{{ $totaltrees }}</div>
                                        <div class="text-sm">Total tags</div>
                                </div>
                            </div>
                        </div>

                    <!-- Footer link -->
                        <a href="{{ route('analytics.carbon') }}" 
                            class="block bg-white text-black/90 border-solid border-2 border-[#1F7D53] hover:bg-[#003300] hover:text-white text-center py-3 w-full transition duration-200 shadow-inner rounded-b-lg">
                            <p class="font-semibold tracking-wide">View Details →</p>
                        </a>
                    </div>

                    <!-- Total Pending Card -->
                        <div class="bg-[#255F38] text-white rounded-md shadow-md overflow-hidden w-full">
                            <!-- Main content -->
                            <div class="p-4 flex flex-col items-center justify-center text-center">
                                <div class="flex items-center justify-center mb-2">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ $pendingtree }}</div>
                                        <div class="text-sm">Pending Approval</div>
                                    </div>
                                </div>
                            </div>

                        <!-- Footer link -->
                            <a href="{{ route('pending-geotags.index') }}" 
                                class="block bg-white text-black/90 border-solid border-2 border-[#255F38] hover:bg-[#003300] hover:text-white text-center py-3 w-full transition duration-200 shadow-inner rounded-b-lg">
                                <p class="font-semibold tracking-wide">View Details →</p>
                            </a>
                        </div>

                    <!-- Total Predicted Card -->
                    <div class="bg-[#04471C] text-white rounded-md shadow-md overflow-hidden w-full">
                        <!-- Main content -->
                        <div class="p-4 flex flex-col items-center justify-center text-center">
                            <div class="flex items-center justify-center mb-2">
                                <div class="text-center">
                                        <div class="text-2xl font-bold">{{ number_format($totalPredicted, 2) }} kg</div>
                                        <div class="text-sm">Total Predicted Harvest</div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer link -->
                            <a href="{{ route('analytics.carbon') }}" 
                                class="block bg-white text-black/90 border-solid border-2 border-[#04471C] hover:bg-[#003300] hover:text-white text-center py-3 w-full transition duration-200 shadow-inner rounded-b-lg">
                                <p class="font-semibold tracking-wide">View Details →</p>
                            </a>
                        </div>

                    <!-- Total Carbon Sequestration Card -->
                        <div class="bg-[#0c2d1c] text-white rounded-md shadow-md overflow-hidden w-full">
                            <!-- Main content -->
                            <div class="p-4 flex flex-col items-center justify-center text-center">
                                <div class="flex items-center justify-center mb-2">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold">{{ $totalAnnualSequestrationKg }}kg co2</div>
                                        <div class="text-sm">Total Carbon Sequestration</div>
                                    </div>
                                </div>
                            </div>
                    <!-- Footer link -->
                        <a href="{{ route('analytics.carbon') }}" 
                            class="block bg-white text-black/90 border-solid border-2 border-[#0c2d1c] hover:bg-[#003300] hover:text-white text-center py-3 w-full transition duration-200 rounded-b-lg shadow-inner">
                            <p class="font-semibold tracking-wide">View Details →</p>
                        </a>
                </div>

                </div>
            </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 px-6 pt-6 w-full">
                    <!-- Pie Chart Section -->
                    <div class="flex flex-col md:flex-row items-center justify-center gap-6 p-4 text-black/90 text-center bg-[#e9eee9] rounded-lg w-full">
                        
                        <!-- Pie Chart -->
                        <div class="flex justify-center items-center sm:w-36 sm:h-36 md:w-56 md:h-56 lg:w-66 lg:h-66 relative">
                            <canvas 
                                id="treePieChart" class="w-40 h-40 sm:w-28 sm:h-28 md:w-38 md:h-38 lg:w-48 lg:h-48"></canvas>
                        </div>

                        <!-- Legend -->
                        <div 
                            id="chartLegend" class="mt-4 md:mt-0 grid grid-cols-1 gap-4 text-sm p-4 w-full md:w-auto"></div>
                    </div>

                    <!-- Harvest Chart Section -->
                    <div class="flex flex-col items-center justify-center bg-[#e9eee9] p-4 rounded-lg w-full h-full">
                        <div class="relative w-full">
                            <canvas id="harvestChart"></canvas>
                        </div>
                    </div>
                </div>
            <br>
        </x-card>
    </section>

</main>

        <!-- Reminders -->
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Reminders">
                <div class="text-l text-black/90 space-y-0.5">
                    <p class="text-gray-500">No reminders yet.</p>
                </div>
            </x-card>
        </section>
    </main>
    <script>
document.getElementById('harvestFilterForm').addEventListener('submit', function(e) {
    e.preventDefault();

    let form = this;
    let formData = new FormData(form);
    let params = new URLSearchParams(formData).toString();

    fetch("{{ route('harvest.filter') }}?" + params, {
        headers: {
            "X-Requested-With": "XMLHttpRequest"
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('harvest-records-container').innerHTML = data.html;
    })
    .catch(error => console.error('Error:', error));
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('harvestChart').getContext('2d');
    const months = @json($months);
    const totals = @json($totals);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Predicted Harvest (kg)',
                data: totals,
                backgroundColor: '#0A400C', // Tailwind sky-400
                borderColor: '#0A400C', // Tailwind sky-500
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Predicted Harvests',
                    font: { size: 14, weight: 'bold' },
                    color: '#000000'
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Month' },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Quantity (kg)' },
                    ticks: { stepSize: 10 }
                }
            }
        }
    });
});
</script>

@endsection