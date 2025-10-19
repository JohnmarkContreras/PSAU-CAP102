@extends('layouts.app')

@section('title', 'Harvest Management')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Harvest Management">
                    <div class="text-sm text-black/90 space-y-0.5">
                    @if (session('success'))
                        <div class="mb-4 rounded-lg bg-green-50 text-green-700 p-3">{{ session('success') }}</div>
                    @endif

                    {{-- Import Excel --}}
                    <form action="{{ route('harvest.import') }}" method="POST" enctype="multipart/form-data" class="mb-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <input type="file" name="file" class="w-full border rounded-lg p-2" required>
                            </div>
                            <button class="rounded-xl bg-blue-600 text-white py-2 px-4 hover:bg-blue-700">
                                Import Excel
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Columns: code (or tree_id), harvest_date (YYYY-MM-DD), harvest_weight_kg, quality, notes</p>
                    </form>

                    {{-- Manual Entry --}}
                    <form action="{{ route('harvest.store') }}" method="POST" class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Tree</label>
                            <select name="code" class="w-full border rounded-lg p-2">
                                @foreach($codes as $tc)
                                    <option value="{{ $tc->code }}">{{ $tc->code }}</option>
                                @endforeach
                            </select>
                            @error('code') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Harvest Date</label>
                            <input type="date" name="harvest_date" class="w-full border rounded-lg p-2" required>
                            @error('harvest_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Weight (kg)</label>
                            <input type="number" step="0.01" name="harvest_weight_kg" class="w-full border rounded-lg p-2" required>
                            @error('harvest_weight_kg') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Quality</label>
                            <input type="text" name="quality" placeholder="A/B/C" class="w-full border rounded-lg p-2">
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button class="w-full rounded-xl bg-blue-600 text-white py-2 px-4 hover:bg-blue-700">Add Harvest</button>
                        </div>
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" class="w-full border rounded-lg p-2" rows="2"></textarea>
                        </div>
                    </form>

                    {{-- Search / Filters --}}
                    <form method="get" class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-2">
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Search code..." class="border rounded px-3 py-2 md:col-span-2">
                        <select name="sort" class="border rounded px-3 py-2">
                            <option value="code" {{ ($sort ?? '')==='code' ? 'selected' : '' }}>Sort by Code</option>
                            <option value="dbh" {{ ($sort ?? '')==='dbh' ? 'selected' : '' }}>Sort by DBH</option>
                            <option value="height" {{ ($sort ?? '')==='height' ? 'selected' : '' }}>Sort by Height</option>
                            <option value="records" {{ ($sort ?? '')==='records' ? 'selected' : '' }}>Sort by Records</option>
                        </select>
                        <select name="dir" class="border rounded px-3 py-2">
                            <option value="asc" {{ ($dir ?? '')==='asc' ? 'selected' : '' }}>Asc</option>
                            <option value="desc" {{ ($dir ?? '')==='desc' ? 'selected' : '' }}>Desc</option>
                        </select>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="yielding" value="1" {{ request('yielding') ? 'checked' : '' }}> Yielding only (≥ {{ $minDbh }}cm & ≥ {{ $minHeight }}m)</label>
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="has_records" value="1" {{ request('has_records') ? 'checked' : '' }}> With records only</label>
                        <div>
                            <button class="rounded-lg bg-emerald-600 text-white py-2 px-4">Apply</button>
                        </div>
                    </form>
                </x-card>
        </section>

        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Tamarind harvests">
                {{-- Trees + Predictions + Past Harvests --}}
                <div class="space-y-6">
                    {{-- Predict buttons --}}
                    <div class="mb-6 flex items-center justify-between flex-wrap gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button id="predict-all-btn"
                                class="rounded-xl bg-emerald-600 text-white py-2 px-4 hover:bg-emerald-700">
                                Predict All (SARIMA 4,1,4 or fallback)
                            </button>
                            <button id="predict-yielding-btn"
                                class="rounded-xl bg-amber-600 text-white py-2 px-4 hover:bg-amber-700">
                                Predict Yielding Only
                            </button>
                            <button id="toggle-all-tables"
                                class="rounded-xl bg-gray-600 text-white py-2 px-4 hover:bg-gray-700">
                                Toggle All Tables
                            </button>
                        </div>
                        <span class="text-xs text-gray-600">
                            Season months: {{ config('services.harvest.harvest_months','1,2,3') }}
                        </span>
                    </div>

                    {{-- Tree Harvest Tables (Collapsible) --}}
                    @forelse ($codes as $tc)
                        @php
                            $hs = \App\Harvest::where('code', $tc->code)
                                    ->orderBy('harvest_date', 'desc')
                                    ->paginate(50);
                            $hasHarvests = $hs->count() > 0;
                        @endphp

                        @if($hasHarvests)
                            <div class="rounded-2xl border p-4 bg-white shadow-sm harvest-tree-card">
                                {{-- Header (Clickable) --}}
                                <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-2 rounded transition -m-2 px-2"
                                     onclick="toggleHarvestTable({{ $tc->id }})">
                                    <div class="flex-1 flex items-center gap-3">
                                        <span class="text-xl text-gray-600 collapse-icon" id="icon-{{ $tc->id }}">▼</span>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                Code <span class="font-mono">{{ $tc->code }}</span>
                                                @if($tc->is_yielding)
                                                    <span class="ml-2 text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">
                                                        Yielding
                                                    </span>
                                                @endif
                                            </h3>
                                                @if($tc->latestPrediction)
                                                    <p class="text-sm text-gray-600">
                                                        Predicted next harvest:
                                                        <span class="font-medium">
                                                            {{ \Carbon\Carbon::parse($tc->latestPrediction->predicted_date)->toFormattedDateString() }}
                                                        </span>
                                                        — ~ <span class="font-medium">
                                                            {{ number_format($tc->latestPrediction->predicted_quantity, 2) }}
                                                        </span> kg
                                                    </p>
                                            @else
                                                <p class="text-sm text-gray-500">No prediction yet.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Harvest Table (Collapsible) --}}
                                <div class="harvest-table-container mt-4" id="container-{{ $tc->id }}">
                                    <div class="overflow-x-auto">
                                        <table id="harvestTable_{{ $tc->id }}" 
                                                class="harvest-table w-full text-sm text-left text-gray-700 border rounded-lg">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2">Date</th>
                                                    <th class="px-4 py-2">Weight (kg)</th>
                                                    <th class="px-4 py-2">Quality</th>
                                                    <th class="px-4 py-2">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @foreach ($hs as $h)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-4 py-2">
                                                            {{-- Hidden raw date for correct DataTables sorting --}}
                                                            <span style="display:none;">{{ $h->harvest_date }}</span>
                                                            {{ \Carbon\Carbon::parse($h->harvest_date)->toFormattedDateString() }}
                                                        </td>
                                                        <td class="px-4 py-2">{{ number_format($h->harvest_weight_kg, 2) }}</td>
                                                        <td class="px-4 py-2">{{ $h->quality ?? '—' }}</td>
                                                        <td class="px-4 py-2">{{ $h->notes ?? '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="rounded-lg p-6 bg-white text-center text-gray-500">
                            <p>No trees with harvest records found.</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </section>

        {{-- Calendar Section --}}
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Harvest Predictions Calendar">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form action="{{ route('send.reminders') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary float-right h-10 pl-10 pr-10 text-white rounded-md bg-blue-600 hover:bg-blue-700">
                        Send Harvest Reminders
                    </button>
                </form>

                <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

                <div class="mt-8 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Harvest Calendar</h2>
                    <div class="text-sm text-gray-600 mb-4">
                        <p><span class="inline-block w-4 h-4 bg-green-500 rounded mr-2"></span>✓ = Actual Harvest</p>
                        <p><span class="inline-block w-4 h-4 bg-blue-400 rounded mr-2"></span>📅 = Predicted Harvest</p>
                    </div>
                    <div id="calendar-wrapper" class="w-full">
                        <div id="harvest-calendar" class="bg-white p-2 sm:p-4 rounded-xl shadow-md"></div>
                    </div>
                </div>

                <!-- Harvest vs Prediction Chart -->
                <div class="mt-8">
                    <canvas id="harvestChart"></canvas>
                </div>
                <!-- Event Info Modal -->
                <div id="eventModal"
                    class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 px-4 sm:px-0">
                    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl text-center">
                        <h3 id="modalType" class="text-lg sm:text-xl font-semibold mb-2"></h3>
                        <p class="text-gray-700 mb-1"><strong>Tree:</strong> <span id="modalCode"></span></p>
                        <p class="text-gray-700 mb-1"><strong>Quantity:</strong> <span id="modalQuantity"></span> kg</p>
                        <p class="text-gray-700 mb-4"><strong>Date:</strong> <span id="modalDate"></span></p>
                        <button id="closeModal"
                                class="mt-2 bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 w-full sm:w-auto">
                            Close
                        </button>
                    </div>
                </div>
            </x-card>
        </section>
    </main>

    <style>
        .collapse-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .harvest-table-container {
            transition: all 0.3s ease;
            max-height: 1000px;
            overflow: hidden;
        }

        .harvest-table-container.collapsed {
            max-height: 0;
            opacity: 0;
        }
    </style>

    <script>
        let tableStates = {}; // Track which tables are open/closed

        function toggleHarvestTable(treeId) {
            const container = document.getElementById(`container-${treeId}`);
            const icon = document.getElementById(`icon-${treeId}`);
            
            if (container.classList.contains('collapsed')) {
                container.classList.remove('collapsed');
                icon.textContent = '▼';
                tableStates[treeId] = true;
            } else {
                container.classList.add('collapsed');
                icon.textContent = '▶';
                tableStates[treeId] = false;
            }
        }

        // Toggle all tables
        document.getElementById('toggle-all-tables').addEventListener('click', function() {
            const containers = document.querySelectorAll('.harvest-table-container');
            const allCollapsed = Array.from(containers).every(c => c.classList.contains('collapsed'));
            
            containers.forEach(container => {
                const treeId = container.id.replace('container-', '');
                const icon = document.getElementById(`icon-${treeId}`);
                
                if (allCollapsed) {
                    container.classList.remove('collapsed');
                    icon.textContent = '▼';
                    tableStates[treeId] = true;
                } else {
                    container.classList.add('collapsed');
                    icon.textContent = '▶';
                    tableStates[treeId] = false;
                }
            });
        });

        async function runPredict(yieldingOnly = false) {
            const url = `{{ route('harvest.predictAll') }}`;
            const res = await fetch(url + (yieldingOnly ? '?yielding=1' : ''), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            return res.json();
        }

        document.getElementById('predict-all-btn').addEventListener('click', async () => {
            const btn = document.getElementById('predict-all-btn');
            btn.disabled = true;
            const old = btn.textContent;
            btn.textContent = 'Predicting...';

            try {
                const data = await runPredict(false);
                console.log("Prediction results:", data);

                if (!data.ok) {
                    alert('Prediction failed.');
                } else {
                    let summary = '';
                    for (const [code, result] of Object.entries(data.results)) {
                        if (result.ok) {
                            summary += `Tree ${code}: Predicted ${result.predicted_quantity}kg on ${result.predicted_date}\n`;
                        } else {
                            summary += `Tree ${code}: ⚠️ ${result.message}\n`;
                        }
                    }
                    alert(summary);
                }
            } catch (e) {
                console.error(e);
                alert('Prediction error');
            } finally {
                btn.disabled = false;
                btn.textContent = old;
            }
        });

        document.getElementById('predict-yielding-btn').addEventListener('click', async () => {
            const btn = document.getElementById('predict-yielding-btn');
            btn.disabled = true;
            const old = btn.textContent;
            btn.textContent = 'Predicting...';
            
            try {
                const data = await runPredict(true);
                if (!data.ok) {
                    alert('Prediction failed.');
                    return;
                }
                alert('Predicted yielding trees only.');
            } catch (e) {
                console.error(e);
                alert('Prediction error');
            } finally {
                btn.disabled = false;
                btn.textContent = old;
            }
        });

        // Calendar initialization
        document.addEventListener("DOMContentLoaded", function () {
            const allEvents = @json($allCalendarEvents);

            console.log("Total events to render:", allEvents.length);

            if (!allEvents || allEvents.length === 0) {
                console.warn("No events found");
                return;
            }

            const calendarEl = document.getElementById('harvest-calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                timeZone: 'Asia/Manila',
                initialView: window.innerWidth < 640 ? 'listMonth' : 'dayGridMonth',
                height: 'auto',
                contentHeight: 'auto',
                expandRows: true,
                headerToolbar: {
                    left: window.innerWidth < 640 ? 'prev,next' : 'prev,next today',
                    center: 'title',
                    right: window.innerWidth < 640 ? '' : 'dayGridMonth,listMonth'
                },
                views: {
                    dayGridMonth: {
                        titleFormat: { year: 'numeric', month: 'long' },
                        dayMaxEventRows: 2,
                    },
                    listMonth: {
                        titleFormat: { year: 'numeric', month: 'long' },
                    },
                },
                windowResize: function (view) {
                    // Switch layout on window resize
                    if (window.innerWidth < 640 && calendar.view.type !== 'listMonth') {
                        calendar.changeView('listMonth');
                    } else if (window.innerWidth >= 640 && calendar.view.type !== 'dayGridMonth') {
                        calendar.changeView('dayGridMonth');
                    }
                },
                events: allEvents,
                eventClick: function(info) {
                    const event = info.event;

                    // Get references to modal and its fields
                    const modal = document.getElementById('eventModal');
                    const typeEl = document.getElementById('modalType');
                    const codeEl = document.getElementById('modalCode');
                    const quantityEl = document.getElementById('modalQuantity');
                    const dateEl = document.getElementById('modalDate');

                    // Compute values
                    const type = event.extendedProps?.type === 'actual' ? '✓ Actual Harvest' : '📅 Predicted';
                    const code = event.extendedProps?.code || 'Unknown';
                    const quantity = event.extendedProps?.quantity || 'N/A';
                    const date = new Date(event.start).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        timeZone: 'Asia/Manila'
                    });

                    // Fill modal fields
                    typeEl.textContent = type;
                    codeEl.textContent = code;
                    quantityEl.textContent = quantity;
                    dateEl.textContent = date;

                    // Show modal
                    modal.classList.remove('hidden');
                }
            });

            // Close modal
            document.getElementById('closeModal').addEventListener('click', () => {
                document.getElementById('eventModal').classList.add('hidden');
            });

            // Optional: close when clicking outside
            document.getElementById('eventModal').addEventListener('click', e => {
                if (e.target.id === 'eventModal') e.target.classList.add('hidden');
            });

            calendar.render();
            console.log("Calendar rendered successfully");

            // Chart initialization
            const ctx = document.getElementById('harvestChart');
            if (ctx) {
                const evaluation = @json($evaluation);
                
                if (evaluation && evaluation.monthly && evaluation.monthly.length > 0) {
                    const monthlyData = evaluation.monthly;
                    const labels = monthlyData.map(m => m.predicted_date);
                    const predicted = monthlyData.map(m => m.predicted_quantity);
                    const actual = monthlyData.map(m => m.harvest_weight_kg);

                    new Chart(ctx.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Predicted',
                                    data: predicted,
                                    borderColor: 'rgba(59, 130, 246, 1)',
                                    tension: 0.1,
                                    fill: false,
                                },
                                {
                                    label: 'Actual',
                                    data: actual,
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    tension: 0.1,
                                    fill: false,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Predicted vs Actual Tamarind Harvest'
                                },
                                legend: {
                                    display: true
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    </script>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.js/1.13.6/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables.js/1.13.6/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function () {
            $('.harvest-table').each(function () {
                $(this).DataTable({
                    responsive: true,
                    pageLength: 5,
                    ordering: true,
                    order: [[0, 'desc']],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search harvests...",
                        lengthMenu: "Show _MENU_ records",
                        info: "Showing _START_ to _END_ of _TOTAL_ harvests",
                        infoEmpty: "No data available",
                        paginate: {
                            previous: "← Prev",
                            next: "Next →"
                        }
                    }
                });
            });
        });
    </script>
    @endpush

@endsection