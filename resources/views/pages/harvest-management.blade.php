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
                            <button class="rounded-xl bg-indigo-600 text-white py-2 px-4 hover:bg-indigo-700">
                                Import Excel
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Columns: code (or tree_id), harvest_date (YYYY-MM-DD), harvest_weight_kg, quality, notes</p>
                    </form>

                    {{-- Manual Entry --}}
<div class="bg-white shadow-md rounded-2xl p-6 mb-8 border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11V5a1 1 0 10-2 0v2H7a1 1 0 000 2h2v2a1 1 0 102 0V9h2a1 1 0 100-2h-2z" clip-rule="evenodd" />
        </svg>
        Add Harvest Record
    </h2>

                        <form action="{{ route('harvest.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            @csrf

                            <!-- Tree Code -->
                            <div class="col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tree Code</label>
                                <select name="code" class="w-full border-gray-300 focus:ring-green-500 focus:border-green-500 rounded-lg p-2">
                                    <option value="">Select tree...</option>
                                    @foreach($codes as $tc)
                                        <option value="{{ $tc->code }}">{{ $tc->code }}</option>
                                    @endforeach
                                </select>
                                @error('code') 
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Harvest Date -->
                            <div class="col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Harvest Date</label>
                                <input type="date" name="harvest_date" class="w-full border-gray-300 focus:ring-green-500 focus:border-green-500 rounded-lg p-2" required>
                                @error('harvest_date') 
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Harvest Weight -->
                            <div class="col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Weight (kg)</label>
                                <input type="number" step="0.5" name="harvest_weight_kg" placeholder="e.g. 12.5" class="w-full border-gray-300 focus:ring-green-500 focus:border-green-500 rounded-lg p-2" required>
                                @error('harvest_weight_kg') 
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                @enderror
                            </div>

                            <!-- Quality -->
                            <div class="col-span-1">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Quality</label>
                                <input type="text" name="quality" placeholder="A / B / C" class="w-full border-gray-300 focus:ring-green-500 focus:border-green-500 rounded-lg p-2">
                            </div>

                            <!-- Submit Button -->
                            <div class="col-span-1 flex items-end">
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-sm transition-all">
                                    Add Harvest
                                </button>
                            </div>

                            <!-- Notes -->
                            <div class="md:col-span-5">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2" placeholder="Optional notes..." class="w-full border-gray-300 focus:ring-green-500 focus:border-green-500 rounded-lg p-2"></textarea>
                            </div>
                        </form>
                    </div>


                    {{--Search / Filters --}}
                        <form method="get" class="mb-6 bg-white shadow-sm border border-gray-200 rounded-2xl p-4">
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Tree Code</label>
                                    <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="Enter tree code..."
                                        class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                    <select name="sort"
                                        class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="code" {{ ($sort ?? '')==='code' ? 'selected' : '' }}>Tree Code</option>
                                        <option value="dbh" {{ ($sort ?? '')==='dbh' ? 'selected' : '' }}>DBH</option>
                                        <option value="height" {{ ($sort ?? '')==='height' ? 'selected' : '' }}>Height</option>
                                        <option value="records" {{ ($sort ?? '')==='records' ? 'selected' : '' }}>Records</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Direction</label>
                                    <select name="dir"
                                        class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="asc" {{ ($dir ?? '')==='asc' ? 'selected' : '' }}>Ascending</option>
                                        <option value="desc" {{ ($dir ?? '')==='desc' ? 'selected' : '' }}>Descending</option>
                                    </select>
                                </div>

                                <div class="flex flex-col md:col-span-2 gap-1">
                                    <label class="inline-flex items-center text-sm text-gray-700">
                                        <input type="checkbox" name="yielding" value="1" {{ request('yielding') ? 'checked' : '' }}
                                            class="rounded text-emerald-600 border-gray-300 mr-2">
                                        Yielding only (≥ {{ $minDbh }}cm & ≥ {{ $minHeight }}m)
                                    </label>
                                    <label class="inline-flex items-center text-sm text-gray-700">
                                        <input type="checkbox" name="has_records" value="1" {{ request('has_records') ? 'checked' : '' }}
                                            class="rounded text-emerald-600 border-gray-300 mr-2">
                                        With records only
                                    </label>
                                </div>

                                <div>
                                    <button
                                        class="w-full rounded-lg bg-emerald-600 text-white py-2 px-4 hover:bg-emerald-700 transition-colors">
                                        Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- 🌴 Trees + Predictions + Past Harvests --}}
                        <div class="space-y-6">
                            <div class="mb-6 flex flex-col md:flex-row items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2">
                                    <button id="predict-all-btn"
                                        class="rounded-xl bg-emerald-600 text-white py-2 px-4 hover:bg-emerald-700 shadow-sm transition">
                                        Predict All (SARIMA 4,1,4 or fallback)
                                    </button>
                                    <button id="predict-yielding-btn"
                                        class="rounded-xl bg-emerald-600 text-white py-2 px-4 hover:bg-amber-700 shadow-sm transition">
                                        Predict Yielding Only
                                    </button>
                                </div>
                                <span class="text-xs text-gray-500">
                                    Season months: {{ config('services.harvest.harvest_months','12,1,2,3') }}
                                </span>
                            </div>

                            @foreach ($codes as $tc)
                                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition p-5">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                Tree Code: <span class="font-mono text-emerald-700">{{ $tc->code }}</span>
                                                @if($tc->is_yielding)
                                                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">Yielding</span>
                                                @endif
                                            </h3>

                                            @if($tc->latestPrediction)
                                                <p class="text-sm text-gray-600 mt-1">
                                                    Predicted next harvest:
                                                    <span class="font-medium text-gray-800">
                                                        {{ \Carbon\Carbon::parse($tc->latestPrediction->predicted_date)->toFormattedDateString() }}
                                                    </span>
                                                    — approx. <span class="font-medium">{{ number_format($tc->latestPrediction->predicted_quantity, 2) }}</span> kg
                                                </p>
                                            @else
                                                <p class="text-sm text-gray-500 mt-1">No prediction yet.</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto border rounded-lg">
                                        <table class="w-full text-sm text-left text-gray-700">
                                            <thead class="bg-gray-50">
                                                <tr class="text-gray-700 font-semibold">
                                                    <th class="px-4 py-2">Date</th>
                                                    <th class="px-4 py-2">Weight (kg)</th>
                                                    <th class="px-4 py-2">Quality</th>
                                                    <th class="px-4 py-2">Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y">
                                                @php
                                                    $hs = \App\Harvest::where('code', $tc->code)
                                                        ->orderBy('harvest_date', 'desc')
                                                        ->take(10)
                                                        ->get();
                                                @endphp
                                                @forelse ($hs as $h)
                                                    <tr class="hover:bg-gray-50 transition">
                                                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($h->harvest_date)->toFormattedDateString() }}</td>
                                                        <td class="px-4 py-2">{{ number_format($h->harvest_weight_kg, 2) }}</td>
                                                        <td class="px-4 py-2">{{ $h->quality ?? '—' }}</td>
                                                        <td class="px-4 py-2">{{ $h->notes ?? '—' }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-3 text-center text-gray-500 italic">
                                                            No harvest records yet.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>


                    {{-- Calendar placeholder (per-tree and all-trees will render via JS later) --}}
                    <div id="calendar" class="mt-8 border rounded p-4 bg-white">
                        <div class="text-sm text-gray-700 font-semibold mb-2">Best Harvest Calendar</div>
                        <div id="calendar-all" class="text-xs text-gray-600 mb-4">All trees</div>
                        <div id="calendar-per-tree" class="text-xs text-gray-600">Per tree</div>
                    </div>
                

                    <script>
                        async function runPredict(yieldingOnly = false) {
                            const url = `{{ route('harvest.predictAll') }}`;
                            const res = await fetch(url + (yieldingOnly ? '?yielding=1' : ''), {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                            });
                            return res.json();
                        }

                        function renderCalendar(data) {
                            try {
                                const allEl = document.getElementById('calendar-all');
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
                                allEl.textContent = allSummary || 'No predictions';

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
                                            summary += `Tree ${code}: ✅ Predicted ${result.predicted_quantity}kg on ${result.predicted_date}\n`;
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
                </x-card>
        </section>
    </main>
@endsection