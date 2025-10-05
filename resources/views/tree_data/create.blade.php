@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Farm Data')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="Add Tamarind Tree Data">
                <div class="text-sm text-black/90 space-y-4 z-50">
                    <form action="{{ route('tree_data.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Tree Code Selection -->
                            <div>
                                <label for="tree_code_id" class="block font-semibold">Tree Code</label>
                                @if(!empty($defaultCodeId) && $treeCodes->contains('id', $defaultCodeId))
                                    @php
                                        $currentCode = $treeCodes->firstWhere('id', $defaultCodeId);
                                    @endphp
                                    <input type="text" 
                                        class="w-full border rounded p-2 bg-gray-100 cursor-not-allowed" 
                                        value="{{ $currentCode->code }}" 
                                        readonly>
                                    <input type="hidden" name="tree_code_id" value="{{ $currentCode->id }}">
                                @endif
                            </div>

                        <!-- DBH -->
                        <div>
                            <label for="dbh" class="block font-semibold">DBH (cm)</label>
                            <input type="text" pattern="\d+(\.\d{1,2})?" title="Enter a valid number" name="dbh" id="dbh" class="w-full border rounded p-2" required>
                        </div>

                        <!-- Height -->
                        <div>
                            <label for="height" class="block font-semibold">Height (m)</label>
                            <input type="text" pattern="\d+(\.\d{1,2})?" title="Enter a valid number" name="height" id="height" class="w-full border rounded p-2" required>
                        </div>

                        <!-- Age (optional) -->
                        <div>
                            <label for="age" class="block font-semibold">Age (years)</label>
                            <input type="text" pattern="\d+(\.\d{1,2})?" title="Enter a valid number" name="age" id="age" class="w-full border rounded p-2">
                        </div>

                        <!-- Stem Diameter (optional) -->
                        <div>
                            <label for="stem_diameter" class="block font-semibold">Stem Diameter (cm)</label>
                            <input type="text" pattern="\d+(\.\d{1,2})?" title="Enter a valid number" name="stem_diameter" id="stem_diameter" class="w-full border rounded p-2">
                        </div>

                        <!-- Canopy Diameter (optional) -->
                        <div>
                            <label for="canopy_diameter" class="block font-semibold">Canopy Diameter (m)</label>
                            <input type="text" pattern="\d+(\.\d{1,2})?" title="Enter a valid number" name="canopy_diameter" id="canopy_diameter" class="w-full border rounded p-2">
                        </div>

                        <p class="text-xs text-gray-600">Only trees meeting minimum thresholds (DBH ≥ 10 cm and Height ≥ 2 m) are used in yield predictions.</p>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                Save Tree Data
                            </button>
                        </div>
                    </form>
                </div>
                @if(session('success'))
                    <div class="text-green-700 font-semibold mb-2">
                        {{ session('success') }}
                    </div>
                @endif
                <!-- Duplicate check error -->
                @if($errors->has('duplicate'))
                    <div class="text-red-600">{{ $errors->first('duplicate') }}</div>
                @endif
            </x-card>
        </section>
    </main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Remove modal if it exists
    const modal = document.getElementById('tree-details');
    if (modal) modal.remove();

    // Remove any bootstrap/tailwind modal backdrops
    document.querySelectorAll('.modal-backdrop, .backdrop').forEach(el => el.remove());

    // Also clear any leftover body styles from modal-open
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
});
</script>
<style>
/* Force inputs clickable in case something is blocking */
input, select, textarea, button {
    pointer-events: auto !important;
    position: relative !important;
    z-index: 10 !important;
}
</style>
@endsection