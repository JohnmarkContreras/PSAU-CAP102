@extends('layouts.app')

@section('content')
<script>
    function fillLocation() {
        navigator.geolocation.getCurrentPosition(function(pos) {
            document.getElementById('latitude').value = pos.coords.latitude.toFixed(8);
            document.getElementById('longitude').value = pos.coords.longitude.toFixed(8);
        }, function(err) {
            alert("Location access denied or unavailable: " + err.message);
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        fillLocation();
    });
</script>

<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Capture Tamarind Tree Locations">
            <div class="w-full bg-white p-6 rounded shadow">

                <!-- Back button -->
                <div class="flex justify-end mb-4">
                    <a href="{{ url()->previous() }}" 
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                        ← Back
                    </a>
                </div>

                <!-- Toast Notifications -->
                @if (session('success'))
                    <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center space-x-3 animate-slide-in">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>

                    <style>
                        @keyframes slideIn {
                            from { opacity: 0; transform: translateX(400px); }
                            to { opacity: 1; transform: translateX(0); }
                        }
                        @keyframes slideOut {
                            from { opacity: 1; transform: translateX(0); }
                            to { opacity: 0; transform: translateX(400px); }
                        }
                        .animate-slide-in {
                            animation: slideIn 0.3s ease-out;
                        }
                        .animate-slide-out {
                            animation: slideOut 0.3s ease-out;
                        }
                    </style>

                    <script>
                        setTimeout(() => {
                            const toast = document.querySelector('.bg-green-500');
                            if (toast) {
                                toast.classList.add('animate-slide-out');
                                setTimeout(() => toast.remove(), 300);
                            }
                        }, 4000);
                    </script>
                @endif

                @if (session('error'))
                    <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center space-x-3 animate-slide-in">
                        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>

                    <style>
                        @keyframes slideIn {
                            from { opacity: 0; transform: translateX(400px); }
                            to { opacity: 1; transform: translateX(0); }
                        }
                        @keyframes slideOut {
                            from { opacity: 1; transform: translateX(0); }
                            to { opacity: 0; transform: translateX(400px); }
                        }
                        .animate-slide-in {
                            animation: slideIn 0.3s ease-out;
                        }
                        .animate-slide-out {
                            animation: slideOut 0.3s ease-out;
                        }
                    </style>

                    <script>
                        setTimeout(() => {
                            const toast = document.querySelector('.bg-red-500');
                            if (toast) {
                                toast.classList.add('animate-slide-out');
                                setTimeout(() => toast.remove(), 300);
                            }
                        }, 4000);
                    </script>
                @endif

                <form action="{{ route('trees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Tree Image -->
                    <div class="mb-4">
                        <label for="filename" class="block text-sm font-medium text-gray-700">Tree Image</label>
                        <input type="file" name="filename" id="filename" accept="image/*" capture="environment" class="form-input w-full @error('filename') border-red-500 @enderror" required>
                        @error('filename')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Location -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-input w-full @error('latitude') border-red-500 @enderror" required value="{{ old('latitude') }}">
                            <button type="button" onclick="fillLocation()" class="text-sm text-blue-600 underline mt-1">
                                Use my current location
                            </button>
                            @error('latitude')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-input w-full @error('longitude') border-red-500 @enderror" required value="{{ old('longitude') }}">
                            @error('longitude')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <!-- Tree Type -->
                    <div class="mb-4">
                        <label for="tree_type_id" class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="tree_type_id" id="tree_type_id" class="form-select w-full mt-1 @error('tree_type_id') border-red-500 @enderror" required>
                            <option value="">Select type</option>
                            @foreach ($treeTypes as $type)
                                <option value="{{ $type->id }}" data-name="{{ ucfirst(str_replace('_', ' ', $type->name)) }}" {{ old('tree_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $type->name)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('tree_type_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Tree Code -->
                    <div class="mb-4">
                        <label for="code" class="block text-sm font-medium text-gray-700">Tree Code</label>
                        <input type="text" name="code" id="code" class="form-input w-full @error('code') border-red-500 @enderror" placeholder="Select a type first" required value="{{ old('code') }}">
                        @error('code')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- DBH -->
                    <div class="mb-4">
                        <label for="dbh" class="block text-sm font-medium text-gray-700">DBH (cm)</label>
                        <input type="number" name="dbh" id="dbh" step="0.01" class="form-input w-full @error('dbh') border-red-500 @enderror" value="{{ old('dbh') }}">
                        @error('dbh')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Height -->
                    <div class="mb-4">
                        <label for="height" class="block text-sm font-medium text-gray-700">Height (m)</label>
                        <input type="number" name="height" id="height" step="0.01" class="form-input w-full @error('height') border-red-500 @enderror" value="{{ old('height') }}">
                        @error('height')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Age -->
                    <div class="mb-4">
                        <label for="age" class="block text-sm font-medium text-gray-700">Age (years)</label>
                        <input type="number" name="age" id="age" class="form-input w-full @error('age') border-red-500 @enderror" value="{{ old('age') }}">
                        @error('age')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <!-- Canopy Diameter -->
                    <div class="mb-4">
                        <label for="canopy_diameter" class="block text-sm font-medium text-gray-700">Canopy Diameter (m)</label>
                        <input type="number" name="canopy_diameter" id="canopy_diameter" step="0.01" class="form-input w-full @error('canopy_diameter') border-red-500 @enderror" value="{{ old('canopy_diameter') }}">
                        @error('canopy_diameter')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
                    </div>

                    <input type="hidden" name="taken_at" id="taken_at">

                    <!-- Submit -->
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                        Submit
                    </button>
                </form>
            </div>
        </x-card>
    </section>
</main>

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
        
        const treeCode = `${typeName}:${codeCounter}`;
        codeInput.placeholder = treeCode;
        codeInput.value = `${typeName}${codeCounter}`;
        
        codeCounter++;
    });
</script>

<script>
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

                if (dateTaken) {
                    document.getElementById('taken_at').value = formatExifDate(dateTaken);
                } else {
                    document.getElementById('taken_at').value = new Date().toISOString().slice(0, 19).replace('T', ' ');
                }
            } catch (err) {
                console.error("EXIF parse error:", err);
            }
        });
    });

    function formatExifDate(dateStr) {
        if (!dateStr) return new Date().toISOString().slice(0, 19).replace('T', ' ');
        const parts = dateStr.split(" ");
        if (parts.length < 2) return new Date().toISOString().slice(0, 19).replace('T', ' ');
        const date = parts[0].replace(/:/g, "-");
        const time = parts[1];
        return `${date}T${time}`;
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

@endsection