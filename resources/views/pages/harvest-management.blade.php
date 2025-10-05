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
                    <form action="{{ route('harvest.store') }}" method="POST" class="mb-8 grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium mb-1">Tree</label>
                            <select name="code" class="w-full border rounded-lg p-2">
                                @foreach($allTrees as $t)
                                    <option value="{{ $t->code }}">{{ $t->code }}</option>
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

                    {{-- Trees + Predictions + Past Harvests --}}

                    <div class="space-y-6">
                        <div class="mb-6 flex items-center justify-between">
                            <button id="predict-all-btn"
                                class="rounded-xl bg-emerald-600 text-white py-2 px-4 hover:bg-emerald-700">
                                Predict All (SARIMA 4,1,4 or fallback)
                            </button>
                            <span class="text-xs text-gray-600">Season months: {{ config('services.harvest.harvest_months','12,1,2,3') }}</span>
                        </div>

                        @foreach ($codes as $tc)
                            <div class="rounded-2xl border p-4">
                                
                                <div class="flex items-center justify-between mb-3">
                                    
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            Code <span class="font-mono">{{ $tc->code }}</span>
                                            @if($tc->is_yielding)
                                                <span class="ml-2 text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">Yielding</span>
                                            @endif
                                        </h3>
                                        @if($tc->latestPrediction)
                                            <p class="text-sm text-gray-600">
                                                Predicted next harvest:
                                                <span class="font-medium">
                                                    {{ \Carbon\Carbon::parse($tc->latestPrediction->predicted_date)->toFormattedDateString() }}
                                                </span>
                                                — ~ <span class="font-medium">{{ number_format($tc->latestPrediction->predicted_quantity, 2) }}</span> kg
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-500">No prediction yet.</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left text-gray-700 border rounded-lg">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2">Date</th>
                                                <th class="px-4 py-2">Weight (kg)</th>
                                                <th class="px-4 py-2">Quality</th>
                                                <th class="px-4 py-2">Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            @php $hs = \App\Harvest::where('code',$tc->code)->orderBy('harvest_date','desc')->take(10)->get(); @endphp
                                            @forelse ($hs as $h)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2">{{ \Carbon\Carbon::parse($h->harvest_date)->toFormattedDateString() }}</td>
                                                    <td class="px-4 py-2">{{ number_format($h->harvest_weight_kg, 2) }}</td>
                                                    <td class="px-4 py-2">{{ $h->quality ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $h->notes ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="px-4 py-3 text-center text-gray-500">No harvest records.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Calendar placeholder (per-tree and all-trees will render via JS later) --}}
                    <div id="calendar" class="mt-8 border rounded p-4 bg-white">
                        <div class="text-sm text-gray-600">Calendar of predicted best harvest dates will appear here.</div>
                    </div>
                

                    <script>
                        document.getElementById('predict-all-btn').addEventListener('click', async () => {
                            const btn = document.getElementById('predict-all-btn');
                            btn.disabled = true; const old = btn.textContent; btn.textContent = 'Predicting...';

                            try {
                                const res = await fetch(`{{ route('harvest.predictAll') }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });

                                const data = await res.json();
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
                                    location.reload();
                                }
                            } catch (e) {
                                console.error(e);
                                alert('Prediction error');
                            } finally {
                                btn.disabled = false; btn.textContent = old;
                            }
                        });
                    </script>
                </x-card>
        </section>
    </main>
@endsection