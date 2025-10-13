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
    <x-card title="Add Tree Harvests">
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
                </div>
                <span class="text-xs text-gray-600">
                    Season months: {{ config('services.harvest.harvest_months','1,2,3') }}
                </span>
            </div>

            {{-- Tree Harvest Tables --}}
            @foreach ($codes as $tc)
                <div class="rounded-2xl border p-4 bg-white shadow-sm">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-3">
                        <div>
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

                    {{-- Harvest Table --}}
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
                                @php
                                    $hs = \App\Harvest::where('code',$tc->code)
                                            ->orderBy('harvest_date','desc')
                                            ->get();
                                @endphp

                                @forelse ($hs as $h)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">
                                            {{ \Carbon\Carbon::parse($h->harvest_date)->toFormattedDateString() }}
                                        </td>
                                        <td class="px-4 py-2">{{ number_format($h->harvest_weight_kg, 2) }}</td>
                                        <td class="px-4 py-2">{{ $h->quality ?? '—' }}</td>
                                        <td class="px-4 py-2">{{ $h->notes ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center text-gray-500">
                                            No harvest records.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</section>
                {{-- Calendar --}}

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
                                <h2 class="text-xl font-semibold text-gray-800 mb-2">Harvest Predictions Calendar</h2>
                                <p class="text-sm text-gray-600">Predicted harvest dates for all trees. Click on a date to see details.</p>
                                    <div id="calendar-wrapper" class="w-full overflow-x-auto">
                                        <div id="harvest-calendar" class="bg-white p-2 sm:p-4 rounded-xl shadow-md min-w-[320px]"></div>
                                    </div>
                            </div>
                    </x-card>
                    <canvas id="harvestChart"></canvas>
                        @if(!empty($evaluation))
                            <div class="mt-4 p-4 bg-gray-100 rounded">
                                <p><strong>Overall Accuracy:</strong> {{ $evaluation['overall_accuracy'] }}%</p>
                                <p><strong>MAPE:</strong> {{ $evaluation['mape'] }}%</p>
                                <p><strong>RMSE:</strong> {{ $evaluation['rmse'] }}</p>
                                <p><strong>Correlation:</strong> {{ $evaluation['correlation'] }}</p>
                            </div>
                        @else
                            <div class="mt-4 p-4 bg-yellow-100 rounded">
                                <p>No evaluation data available yet. Predictions exist, but actual harvest data may not be sufficient for accuracy metrics.</p>
                            </div>
                        @endif
                </section>

                <script>

                    document.addEventListener("DOMContentLoaded", function () {
                        const calendarData = @json($calendarData);

                        const events = Object.entries(calendarData).map(([code, entry]) => ({
                            title: `${code} (${entry.predicted_quantity} kg)`,
                            start: `${entry.predicted_date}T00:00:00`,
                            allDay: true,
                            backgroundColor: '#38bdf8',
                            borderColor: '#0ea5e9',
                            textColor: '#fff',
                        }));

                        const calendarEl = document.getElementById('harvest-calendar');
                        const calendar = new FullCalendar.Calendar(calendarEl, {
                            initialView: window.innerWidth < 640 ? 'listMonth' : 'dayGridMonth',
                            height: 'auto',
                            headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: window.innerWidth < 640 ? 'listMonth' : 'dayGridMonth,listMonth'
                            },
                            events: events,
                            eventClick: function(info) {
                            const event = info.event;
                            alert(`${event.title}\nDate: ${event.start.toISOString().split('T')[0]}`);
                            },
                            windowResize: function(view) {
                            calendar.changeView(window.innerWidth < 640 ? 'listMonth' : 'dayGridMonth');
                            }
                        });

                        calendar.render();
                        });
                    
                    const ctx = document.getElementById('harvestChart').getContext('2d');
                    const monthly = @json($evaluation['monthly'] ?? []);

                    const labels = monthly.map(m => m.predicted_date);
                    const predicted = monthly.map(m => m.predicted_quantity);
                    const actual = monthly.map(m => m.harvest_weight_kg);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Predicted',
                                    data: predicted,
                                    borderColor: 'rgba(59, 130, 246, 1)', // Tailwind blue-500
                                    fill: false,
                                },
                                {
                                    label: 'Actual',
                                    data: actual,
                                    borderColor: 'rgba(16, 185, 129, 1)', // Tailwind emerald-500
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
                                }
                            }
                        }
                    });
                    </script>

                    <script>
                        async function runPredict(yieldingOnly = false) {
                            const url = `{{ route('harvest.predictAll') }}`;
                            const res = await fetch(url + (yieldingOnly ? '?yielding=1' : ''), {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                            });
                            return res.json();
                        }

                        document.addEventListener("DOMContentLoaded", function () {
                            const calendarData = @json(['results' => $calendarData]);
                            renderCalendar(calendarData);
                        });

                        function renderCalendar(data) {
                            try {
                                const perTreeEl = document.getElementById('calendar-per-tree');
                                const entries = Object.entries(data.results || {});
                                const allDates = {};
                                entries.forEach(([code, result]) => {
                                    if (!result.ok) return;
                                    const d = result.predicted_date;
                                    allDates[d] = (allDates[d] || 0) + 1;
                                });
                                const allSummary = Object.entries(allDates)
                                    .sort((a,b)=>a[0].localeCompare(b[0]))
                                    .map(([d,n])=>`${d}: ${n} tree(s)`) 
                                    .join(' | ');

                                // Per tree lines
                                perTreeEl.innerHTML = entries
                                    .filter(([,r])=>r.ok)
                                    .map(([code,r])=>`<div>${code}: ${r.predicted_date} (~${r.predicted_quantity} kg)</div>`)
                                    .join('');
                            } catch (e) { console.error(e); }
                        }

                        document.getElementById('predict-all-btn').addEventListener('click', async () => {
                            const btn = document.getElementById('predict-all-btn');
                            btn.disabled = true; const old = btn.textContent; btn.textContent = 'Predicting...';

                            try {
                                const data = await runPredict(false);
                                console.log("Prediction results:", data);

                                if (!data.ok) {
                                    alert('Prediction failed.');
                                } else {
                                    let summary = '';
                                    for (const [code, result] of Object.entries(data.results)) {
                                        if (result.ok) {
                                            summary += `Tree ${code}:  Predicted ${result.predicted_quantity}kg on ${result.predicted_date}\n`;
                                        } else {
                                            summary += `Tree ${code}: ⚠️ ${result.message}\n`;
                                        }
                                    }
                                    alert(summary);
                                    renderCalendar(data);
                                }
                            } catch (e) {
                                console.error(e);
                                alert('Prediction error');
                            } finally {
                                btn.disabled = false; btn.textContent = old;
                            }
                        });

                        document.getElementById('predict-yielding-btn').addEventListener('click', async () => {
                            const btn = document.getElementById('predict-yielding-btn');
                            btn.disabled = true; const old = btn.textContent; btn.textContent = 'Predicting...';
                            try {
                                const data = await runPredict(true);
                                if (!data.ok) { alert('Prediction failed.'); return; }
                                renderCalendar(data);
                                alert('Predicted yielding trees only.');
                            } catch (e) { console.error(e); alert('Prediction error'); }
                            finally { btn.disabled = false; btn.textContent = old; }
                        });
                    </script>
                    {{-- Calendar rendering scritp --}}
                    <script>
                        document.addEventListener("DOMContentLoaded", function () {
                            // Use your already-passed data
                            const calendarData = @json($calendarData);

                            // Convert predictions into FullCalendar events
                            const events = Object.entries(calendarData).map(([code, entry]) => ({
                                title: `${code} (${entry.predicted_quantity} kg)`,
                                start: `${entry.predicted_date}T00:00:00`,
                                allDay: true,
                                backgroundColor: '#38bdf8', // Tailwind sky-400
                                borderColor: '#0ea5e9',
                                textColor: '#fff',
                            }));

                            // Create the FullCalendar instance
                            const calendarEl = document.getElementById('harvest-calendar');
                            const calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                height: 'auto',
                                headerToolbar: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: 'dayGridMonth,listMonth'
                                },
                                events: events,
                                eventClick: function(info) {
                                    const event = info.event;
                                    alert(`${event.title}\nDate: ${event.start.toISOString().split('T')[0]}`);
                                }
                            });

                            // Render the calendar
                            calendar.render();
                        });
                </script>

                {{-- DataTables for harvest tables --}}
                @push('scripts')
<script>
    $(document).ready(function () {
        // Apply DataTable to all harvest tables
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
                },
                columnDefs: [
                    { orderable: true, targets: [0,1,2,3] }
                ]
            });
        });
    });
</script>
@endpush
    </main>
@endsection