@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Dashboard')

@section('content')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('treePieChart');
        if (!canvas) return;

        const labels = @json(['Sour', 'Sweet', 'Semi-Sweet']);
        const data = @json([ $totalsour ?? 0, $totalsweet ?? 0, $totalsemi_sweet ?? 0 ]);
        const colors = ['#f87171', '#34d399', '#fbbf24'];

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
        const t = Date.parse(String(s));
        return isNaN(t) ? null : new Date(t);
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
        // clone array so we don't mutate original
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
        <!-- Dashboard Summary with Chart -->
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Dashboard">
                <div class="text-l text-black/90 space-y-0.5">
                    <p>Total Number of Trees: {{ $totaltrees }}</p>
                    {{-- <p>Sour Trees: {{ $totalsour }}</p>
                    <p>Sweet Trees: {{ $totalsweet }}</p>
                    <p>Semi-Sweet Trees: {{ $totalsemi_sweet }}</p> --}}
                </div>

                <!-- Chart Container: compact and safe so it won't overlap -->
                <div class="mt-6 flex flex-col md:flex-row items-center justify-center gap-6 w-full max-w-3xl mx-auto">
                    <div class="mt-6 w-full max-w-md mx-auto h-48">
                        <canvas id="treePieChart" class="w-full h-full"></canvas>
                    </div>
                    <div id="chartLegend" class="mt-4 w-full max-w-md mx-auto flex flex-wrap justify-center gap-3 text-sm"></div>
            </x-card>
            <br>
            {{-- harvest-management record --}}
            <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <div class="space-y-4">

                    <!-- Filters -->
                    <form id="harvestFilterForm" class="flex flex-wrap gap-3 items-center">
                        <select name="year" class="border rounded px-3 py-1">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>

                        <select name="month" class="border rounded px-3 py-1">
                            <option value="">All Months</option>
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endfor
                        </select>

                        <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded-lg hover:bg-green-700">
                            Apply
                        </button>
                    </form>


                    <!-- Records Table -->
                    <div class="overflow-x-auto">
                        <div id="harvest-records-container">
                            @include('partials.harvest-table', ['harvests' => $harvests])
                        </div>

                    </div>
                </div>
        </section>

        </section>

        <!-- Notifications -->
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Notification">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="text-l text-black/90 space-y-0.5">
                        <h3 class="font-bold mb-2">Notifications</h3>
                        <ul class="space-y-2">
                            @forelse($notifications as $note)
                                <li class="border-b pb-2">
                                    <p>{{ $note->data['message'] }}</p>
                                    <small class="text-gray-500">{{ $note->created_at->diffForHumans() }}</small>
                                </li>
                            @empty
                                <li>No notifications yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </x-card>
        </section>

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

@endsection