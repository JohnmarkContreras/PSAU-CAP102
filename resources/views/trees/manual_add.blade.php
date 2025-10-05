@extends('layouts.app')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

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
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-200 text-gray-800 px-4 py-1 mb-4 rounded cursor-pointer" onclick="history.back()">Back</button>
                </div>
            <div class="flex items-center justify-between mb-4">
                <div class="w-full bg-white p-6 rounded shadow">

                    <form id="manualTreeForm" action="{{ route('trees.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="filename" class="block text-sm font-medium text-gray-700">Tree Image</label>
                            <input type="file" name="filename" id="filename" accept="image/*" capture="environment" class="form-input w-full" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-input w-full" required>
                                <button type="button" onclick="fillLocation()" class="text-sm text-blue-600 underline mt-1">
                                    📍 Use my current location
                                </button>
                            </div>
                            
                        </div>
                            <div>
                                <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-input w-full" required>
                            </div>

                        <div class="mb-4">
                            <label for="code" class="block text-sm font-medium text-gray-700">Tree Code</label>
                            <input type="text" name="code" id="code" class="form-input w-full" required>
                        </div>

                        <div class="mb-4">
                            <label for="tree_type_id" class="block text-sm font-medium text-gray-700">Type</label>
                            <select name="tree_type_id" id="tree_type_id" class="form-select w-full mt-1" required>
                                <option value="">Select type</option>
                                @foreach ($treeTypes as $type)
                                    <option value="{{ $type->id }}">{{ ucfirst(str_replace('_', ' ', $type->name)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="hidden" name="taken_at" id="taken_at">

                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Submit
                        </button>
                    </form>
                </div>

                <!-- Modal -->
                <div id="feedbackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-lg p-6 w-96">
                        <h3 id="modalTitle" class="text-lg font-semibold mb-2"></h3>
                        <p id="modalMessage" class="text-sm text-gray-700 mb-4"></p>
                        <div class="flex justify-end">
                            <button onclick="closeModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/exif-js"></script>
<script>
    document.getElementById('filename').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        EXIF.getData(file, function() {
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
                // Optionally, set to current date/time if EXIF is missing
                document.getElementById('taken_at').value = new Date().toISOString().slice(0, 19).replace('T', ' ');
            }
        });
    });

    function formatExifDate(dateStr) {
        // EXIF date format: "YYYY:MM:DD HH:MM:SS"
        const parts = dateStr.split(" ");
        const date = parts[0].replace(/:/g, "-");
        const time = parts[1];
        return `${date}T${time}`;
    }

    const form = document.getElementById('manualTreeForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        fetch("{{ route('trees.store') }}", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => Promise.reject(err));
            return response.json();
        })
        .then(data => {
            showModal("Success", "🌳 Tree added to pending trees successfully!");
            form.reset();
        })
        .catch(error => {
            let message = "❌ Something went wrong.";
            if (error.errors && error.errors.code) {
                if (error.errors.code[0].includes('unique')) {
                    message = "Tree already exists.";
                } else {
                    message = error.errors.code[0];
                }
            } else if (error.errors) {
                message = Object.values(error.errors).flat().join(" ");
            }
            showModal("Error", message);
        });
    });

    function convertToDecimal(coord, ref) {
        const d = coord[0].numerator / coord[0].denominator;
        const m = coord[1].numerator / coord[1].denominator;
        const s = coord[2].numerator / coord[2].denominator;
        let decimal = d + (m / 60) + (s / 3600);
        if (ref === "S" || ref === "W") decimal *= -1;
        return decimal.toFixed(8);
    }

    function showModal(title, message) {
        document.getElementById("modalTitle").textContent = title;
        document.getElementById("modalMessage").textContent = message;
        document.getElementById("feedbackModal").classList.remove("hidden");
    }

    function closeModal() {
        document.getElementById("feedbackModal").classList.add("hidden");
    }
</script>
@endsection

