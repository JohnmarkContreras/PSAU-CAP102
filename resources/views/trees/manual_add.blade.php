@extends('layouts.app')

@section('content')
<script>
function fillLocation(force = false) {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const accuracyDisplay = document.getElementById('accuracy-display');

    if (!force && latInput.value && lngInput.value) return;

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            latInput.value = pos.coords.latitude.toFixed(8);
            lngInput.value = pos.coords.longitude.toFixed(8);
            accuracyDisplay.textContent = `(Accuracy: ±${pos.coords.accuracy.toFixed(1)} m)`;
            accuracyDisplay.classList.remove('text-red-600');
            accuracyDisplay.classList.add('text-green-600');
        },
        function(err) {
            accuracyDisplay.textContent = "Location access denied or unavailable";
            accuracyDisplay.classList.remove('text-green-600');
            accuracyDisplay.classList.add('text-red-600');
            alert("Location access denied or unavailable: " + err.message);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

document.addEventListener("DOMContentLoaded", function () {
    fillLocation(true);
});
</script>

<main class="flex-1 p-4 sm:p-6 bg-[#f9faf9] min-h-screen">
    <section class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-6 sm:p-8">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-800">Capture Tamarind Tree Location</h1>
            <a href="{{ url()->previous() }}" 
                class="text-gray-600 text-sm sm:text-base hover:text-gray-800 transition">
                ← Back
            </a>
        </div>

        {{-- FORM --}}
        <form action="{{ route('trees.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Tree Image --}}
            <div>
                <label for="filename" class="block text-sm font-medium text-gray-700 mb-1">Tree Image</label>
                <input type="file" name="filename" id="filename" accept="image/*" capture="environment" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" required>
                @error('filename')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Location --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                    <input type="text" name="latitude" id="latitude" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" 
                        value="{{ old('latitude') }}" required>
                    <button type="button" onclick="fillLocation(true)" class="text-sm text-blue-600 underline mt-1">
                        Refresh Location
                    </button>
                    <div id="accuracy-display" class="text-xs mt-1 text-gray-500 italic"></div>
                    @error('latitude')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                    <input type="text" name="longitude" id="longitude" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" 
                        value="{{ old('longitude') }}" required>
                    @error('longitude')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Type --}}
            <div>
                <label for="tree_type_id" class="block text-sm font-medium text-gray-700 mb-1">Tree Type</label>
                <select name="tree_type_id" id="tree_type_id" 
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" required>
                    <option value="">Select type</option>
                    @foreach ($treeTypes as $type)
                        <option value="{{ $type->id }}" data-name="{{ ucfirst(str_replace('_', ' ', $type->name)) }}" {{ old('tree_type_id') == $type->id ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $type->name)) }}
                        </option>
                    @endforeach
                </select>
                @error('tree_type_id')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Code --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Tree Code</label>
                <input type="text" name="code" id="code" placeholder="Select a type first"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500" 
                    value="{{ old('code') }}" required>
                @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Measurements --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700 mb-1">DBH (cm)</label>
                    <input type="number" name="dbh" id="dbh" step="0.01" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                        value="{{ old('dbh') }}">
                </div>

                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700 mb-1">Height (m)</label>
                    <input type="number" name="height" id="height" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                        value="{{ old('height') }}">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age (years)</label>
                    <input type="number" name="age" id="age" 
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                        value="{{ old('age') }}">
                </div>

                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700 mb-1">Canopy Diameter (m)</label>
                    <input type="number" name="canopy_diameter" id="canopy_diameter" step="0.01"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-green-500 focus:border-green-500"
                        value="{{ old('canopy_diameter') }}">
                </div>
            </div>

            <input type="hidden" name="taken_at" id="taken_at">

            {{-- Submit --}}
            <div class="pt-2">
                <button type="submit" 
                    class="w-full sm:w-auto bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition">
                    Submit
                </button>
            </div>
        </form>
    </section>
</main>

{{-- EXIF Image Data --}}
<script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
<script>
const treeTypeSelect = document.getElementById('tree_type_id');
const codeInput = document.getElementById('code');
let codeCounter = 1;

treeTypeSelect.addEventListener('change', function() {
    if (this.value === '') {
        codeInput.value = '';
        codeInput.placeholder = 'Select a type first';
        codeCounter = 1;
        return;
    }
    const selectedOption = this.options[this.selectedIndex];
    const typeName = selectedOption.dataset.name.toUpperCase().replace(/\s+/g, '_');
    const treeCode = `${typeName}${codeCounter}`;
    codeInput.value = treeCode;
    codeCounter++;
});

document.getElementById('filename').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    EXIF.getData(file, function() {
        try {
            const lat = EXIF.getTag(this, "GPSLatitude");
            const latRef = EXIF.getTag(this, "GPSLatitudeRef");
            const lng = EXIF.getTag(this, "GPSLongitude");
            const lngRef = EXIF.getTag(this, "GPSLongitudeRef");
            const dateTaken = EXIF.getTag(this, "DateTimeOriginal");

            if (lat && lng) {
                document.getElementById('latitude').value = convertToDecimal(lat, latRef);
                document.getElementById('longitude').value = convertToDecimal(lng, lngRef);
            }

            document.getElementById('taken_at').value = dateTaken
                ? formatExifDate(dateTaken)
                : new Date().toISOString().slice(0, 19).replace('T', ' ');
        } catch (err) {
            console.error("EXIF parse error:", err);
        }
    });
});

function formatExifDate(dateStr) {
    if (!dateStr) return new Date().toISOString().slice(0, 19).replace('T', ' ');
    const [date, time] = dateStr.split(" ");
    return `${date.replace(/:/g, "-")} ${time}`;
}

function convertToDecimal(coord, ref) {
    if (!coord) return "";
    const d = coord[0].numerator / coord[0].denominator;
    const m = coord[1].numerator / coord[1].denominator;
    const s = coord[2].numerator / coord[2].denominator;
    let decimal = d + (m / 60) + (s / 3600);
    if (ref === "S" || ref === "W") decimal *= -1;
    return decimal.toFixed(8);
}
</script>

{{-- ✅ Toast Notifications (matching Notifications page) --}}
@if (session('success'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    showToast('success', 'Success!', @json(session('success')));
});
</script>
@endif

@if (session('error'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    showToast('error', 'Error!', @json(session('error')));
});
</script>
@endif
@endsection
