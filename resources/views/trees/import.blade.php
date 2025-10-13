@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Farm Data')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <!-- Excel Upload -->
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Import tree data">
                    <div class="text-sm text-black/90 space-y-0.5">
                        @if(session('success'))
                            <div class="bg-green-100 p-2 text-green-700 rounded">{{ session('success') }}</div>
                        @endif

                        <form action="/trees/import" method="POST" enctype="multipart/form-data" class="p-4 bg-white rounded shadow">
                            @csrf
                            <input type="file" name="file" class="mb-2">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Import Excel</button>
                        </form>
                    </div>
                </x-card>
        </section>

        <!-- Manual Tree Entry -->
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Add Tree Manually">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <form action="/trees/store" method="POST" class="p-4 bg-white rounded shadow space-y-3">
                            @csrf
                            
                            <div>
                                <label class="block text-sm font-semibold">Tree Code</label>
                                <input type="text" name="code" id="treeCodeInput" placeholder="e.g., TM101" required 
                                    class="w-full border px-2 py-1 rounded" list="treeCodeList">
                                <datalist id="treeCodeList"></datalist>
                                <small id="codeError" class="text-red-600 hidden">⚠️ This code already exists.</small>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Type</label>
                                <select name="type" required class="w-full border px-2 py-1 rounded">
                                    <option value="">-- Select Type --</option>
                                    <option value="sweet">Sweet</option>
                                    <option value="sour">Sour</option>
                                    <option value="semi_sweet">Semi-Sweet</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Age (years)</label>
                                <input type="number" name="age" min="0" required 
                                    class="w-full border px-2 py-1 rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Height (m)</label>
                                <input type="number" name="height" step="0.01" min="0" required 
                                    class="w-full border px-2 py-1 rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Stem Diameter (cm)</label>
                                <input type="number" name="stem_diameter" step="0.01" min="0" required 
                                    class="w-full border px-2 py-1 rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Canopy Diameter (m)</label>
                                <input type="number" name="canopy_diameter" step="0.01" min="0" required 
                                    class="w-full border px-2 py-1 rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Latitude</label>
                                <input type="text" name="latitude" id="latitude" readonly 
                                    class="w-full border px-2 py-1 rounded bg-gray-100">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold">Longitude</label>
                                <input type="text" name="longitude" id="longitude" readonly 
                                    class="w-full border px-2 py-1 rounded bg-gray-100">
                            </div>

                            <!-- Refresh Location -->
                            <button type="button" id="refreshLocation" class="bg-blue-500 text-white px-4 py-2 rounded">
                                Update Location
                            </button>

                            <button type="submit" id="saveButton" class="bg-green-600 text-white px-4 py-2 rounded">
                                Save Tree
                            </button>
                        </form>

                        <!-- 🌍 Map Preview -->
                        <div id="map" class="mt-4 rounded border" style="height: 300px;"></div>
                    </div>
                </x-card>
        </section>
    </main>

    <!--  Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let map, marker, accuracyCircle;

    function updateLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    const accuracy = position.coords.accuracy;

                    document.getElementById("latitude").value = lat;
                    document.getElementById("longitude").value = lng;

                    // 🌍 Initialize or update map
                    if (!map) {
                        map = L.map('map').setView([lat, lng], 17);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                    } else {
                        map.setView([lat, lng], 17);
                    }

                    // 📍 Draggable marker
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

                        // when dragging marker, update input + circle
                        marker.on("dragend", function () {
                            let pos = marker.getLatLng();
                            document.getElementById("latitude").value = pos.lat.toFixed(6);
                            document.getElementById("longitude").value = pos.lng.toFixed(6);

                            if (accuracyCircle) {
                                accuracyCircle.setLatLng(pos); // move circle with marker
                            }
                        });
                    }

                    // 🔵 Accuracy circle
                    if (accuracyCircle) {
                        accuracyCircle.setLatLng([lat, lng]).setRadius(accuracy);
                    } else {
                        accuracyCircle = L.circle([lat, lng], {
                            radius: accuracy,
                            color: "blue",
                            fillColor: "#3b82f6",
                            fillOpacity: 0.2
                        }).addTo(map);
                    }
                },
                (error) => {
                    console.error("Geolocation error:", error);
                    alert("Unable to get current location. Error: " + error.message);
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        }
    }

    // Auto update once when page loads
    updateLocation();

    // Update on button click
    document.getElementById("refreshLocation").addEventListener("click", () => {
        updateLocation();
    });

    // === Tree code autocomplete ===
    const treeCodeInput = document.getElementById("treeCodeInput");
    const suggestionsBox = document.createElement("ul");
    suggestionsBox.id = "codeSuggestions";
    suggestionsBox.className = "absolute bg-white border rounded shadow mt-1 max-h-40 overflow-y-auto hidden w-full z-10";
    treeCodeInput.parentNode.appendChild(suggestionsBox);

    let allCodes = [];
    fetch("/trees/codes")
        .then(response => response.json())
        .then(data => {
            allCodes = data;
        });

    treeCodeInput.addEventListener("input", () => {
        const query = treeCodeInput.value.toLowerCase();
        suggestionsBox.innerHTML = "";

        if (!query) {
            suggestionsBox.classList.add("hidden");
            return;
        }

        const filtered = allCodes.filter(code =>
            code.toLowerCase().includes(query)
        );

        if (filtered.length === 0) {
            suggestionsBox.classList.add("hidden");
            return;
        }

        filtered.slice(0, 20).forEach(code => {
            const li = document.createElement("li");
            li.textContent = code;
            li.className = "px-3 py-1 hover:bg-green-100 cursor-pointer";
            li.onclick = () => {
                treeCodeInput.value = code;
                suggestionsBox.classList.add("hidden");
            };
            suggestionsBox.appendChild(li);
        });

        suggestionsBox.classList.remove("hidden");
    });

    // Hide suggestions when clicking outside
    document.addEventListener("click", (e) => {
        if (!suggestionsBox.contains(e.target) && e.target !== treeCodeInput) {
            suggestionsBox.classList.add("hidden");
        }
    });

    // Prevent duplicate submission
    const codeError = document.getElementById("codeError");
    const saveButton = document.getElementById("saveButton");

    treeCodeInput.addEventListener("input", () => {
        fetch(`/trees/check-code?code=${treeCodeInput.value}`)
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    codeError.classList.remove("hidden");
                    saveButton.disabled = true;
                } else {
                    codeError.classList.add("hidden");
                    saveButton.disabled = false;
                }
            });
    });
});
</script>


@endsection
