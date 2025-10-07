@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Tamarind tree location')
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}
@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script>
    var trees = @json($trees);
</script>
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Tamarind Tree Location">
            <div class="flex items-center justify-between mb-4">
                <!-- Search Box -->
                <div class="flex flex-col w-full max-w-md">
                    <div class="relative">
                        <input type="text" id="treeCode" 
                            placeholder="Enter Tree Code (e.g., TM101)" 
                            class="border px-3 py-2 rounded text-sm w-full focus:ring focus:ring-green-300"
                            onkeyup="showSuggestions(this.value)">
                        
                        <!-- Suggestions dropdown -->
                        <ul id="suggestions" 
                            class="absolute z-20 bg-white border w-full rounded shadow mt-1 hidden max-h-40 overflow-y-auto"></ul>
                    </div>

                    <!-- Error Message -->
                    <div id="search-message" class="mt-1 text-red-600 text-sm font-semibold"></div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-2">
                    <a href="{{ route('trees.import') }}" 
                        class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                        + Add Tree
                    </a>
                </div>
            </div>

            <!-- Map -->
            <div id="map" class="h-[500px] w-full rounded z-0"></div>

        <!-- Tree Details Modal -->
        <div id="tree-details"
            class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-white/30 hidden">
            <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-md border border-gray-300">

                <!-- Close Button (Top-Right) -->
                <button onclick="closeTreeDetails()"
                        class="absolute top-2 right-2 text-green-600 hover:text-red-600 text-xl font-bold">
                    close
                </button>
                <!-- Edit Tree Link -->
                <a id="edit-tree-link"
                    href="#"
                    class="absolute top-2 left-2 text-blue-600 hover:text-blue-800 text-sm font-semibold underline">
                    Mark as dead
                </a>

                <h1 class="text-xl font-bold mb-4">Tree Details</h1>
                <p><strong>Code:</strong> <span id="detail-code"></span></p>
                <p><strong>Age:</strong> <span id="detail-age"></span> years</p>
                <p><strong>Height:</strong> <span id="detail-height"></span> m</p>
                <p><strong>Stem Diameter:</strong> <span id="detail-stem"></span> cm</p>
                <p><strong>Canopy Diameter:</strong> <span id="detail-canopy"></span> m</p>
                <p><strong>Status:</strong> <span id="detail-status"></span></p>

                <h4 class="mt-4 font-semibold">Harvest Records</h4>
                <ul id="detail-harvests" class="list-disc list-inside"></ul>
            </div>
        </div>

        </x-card>
    </section>
</main>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

    var map = L.map('map').setView([15.21912622129279, 120.69502532729408], 17);

    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19,
    }).addTo(map);

    var markers = {}; 
    var treeData = {}; 
    var activeMarker = null;

    var defaultIcon = new L.Icon({
        iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
    });
    
    var activeIcon = new L.Icon({
        iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
    });

    // Fetch tree data
    fetch('/trees/data')
        .then(response => response.json())
        .then(data => {
            data.forEach(tree => {
                treeData[tree.code.toUpperCase()] = tree;

                let marker = L.marker([tree.latitude, tree.longitude], { icon: defaultIcon })
                    .addTo(map)
                    .bindPopup(`<strong>${tree.code}</strong>`);

                markers[tree.code.toUpperCase()] = marker;

                marker.on('click', function() {
                    setActiveMarker(marker, tree);
                });
            });
        })
        .catch(error => console.error('Error loading tree data:', error));

    function setActiveMarker(marker, tree) {
    if (activeMarker) {
        activeMarker.setIcon(defaultIcon);
    }
    activeMarker = marker;
    activeMarker.setIcon(activeIcon);
    map.setView(activeMarker.getLatLng(), 18);
    activeMarker.openPopup();

    // ✅ Correct call
    showTreeDetails(tree);
}


    function searchTree() {
        var code = document.getElementById('treeCode').value.trim().toUpperCase();
        var messageDiv = document.getElementById('search-message');

        if (markers[code]) {
            setActiveMarker(markers[code], treeData[code]);
            messageDiv.innerHTML = '';
        } else {
            messageDiv.innerHTML = 'Tree not found on the map';
        }
    }

    function closeTreeDetails() {
    document.getElementById('tree-details').classList.add('hidden');
    
}

    function showTreeDetails(tree) {
    document.getElementById("tree-details").classList.remove("hidden");
    document.getElementById("detail-code").innerText = tree.code;
    document.getElementById("detail-age").innerText = tree.age;
    document.getElementById("detail-height").innerText = tree.height;
    document.getElementById("detail-stem").innerText = tree.stem_diameter;
    document.getElementById("detail-canopy").innerText = tree.canopy_diameter;
    document.getElementById("detail-status").innerText = tree.status;
    // ✅ Set the edit link dynamically here
    document.getElementById('edit-tree-link').href = `/dead-tree-requests/create?tree_code=${tree.code}`;


    // Harvest list
    const harvestList = document.getElementById("detail-harvests");
    harvestList.innerHTML = "";

    if (tree.harvests && tree.harvests.length > 0) {
        tree.harvests.forEach(h => {
            const li = document.createElement("li");
            li.textContent = `${h.harvest_date} — ${h.harvest_weight_kg}kg (Quality: ${h.quality})`;
            harvestList.appendChild(li);
        });
    } else {
        harvestList.innerHTML = "<li><i>No harvest records yet</i></li>";
    }
}

    // Autocomplete suggestions
    function showSuggestions(query) {
        var suggestionsBox = document.getElementById("suggestions");
        suggestionsBox.innerHTML = "";
        
        if (query.length < 1) {
            suggestionsBox.classList.add("hidden");
            return;
        }

        var matches = Object.keys(treeData).filter(code => code.includes(query.toUpperCase()));
        
        if (matches.length === 0) {
            suggestionsBox.classList.add("hidden");
            return;
        }

        matches.forEach(code => {
            let li = document.createElement("li");
            li.className = "px-3 py-2 hover:bg-green-100 cursor-pointer text-sm";
            li.textContent = code;
            li.onclick = function() {
                document.getElementById("treeCode").value = code;
                suggestionsBox.classList.add("hidden");
                searchTree();
            };
            suggestionsBox.appendChild(li);
        });

        suggestionsBox.classList.remove("hidden");
    }
</script>
@endsection
