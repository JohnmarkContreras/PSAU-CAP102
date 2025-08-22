@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Tamarind tree location')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<style>
    #map {
        height: 500px;
        width: 100%;
    }
</style>

<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
            <x-card title="Tamarind tree location">

                <!-- Title + Actions -->
                <div class="flex items-center justify-between mb-4">
                    <!-- Search Box -->
                    <div class="flex flex-col">
                        <div class="flex space-x-2">
                            <input type="text" id="treeCode" 
                                placeholder="Enter Tree Code (e.g., TM101)" 
                                class="border px-2 py-1 rounded text-sm">
                            <button onclick="searchTree()" 
                                    class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                Search
                            </button>
                        </div>
                        <!-- Error Message -->
                        <div id="search-message" class="mt-1 text-red-600 text-sm font-semibold"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-2">
                        <a href="{{ route('trees.import') }}" 
                            class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                            Add Tree
                        </a>
                    </div>
                </div>

                <!-- Map -->
                <div id="map"></div>
            </x-card>
        </h2>
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

    fetch('/trees/data')
        .then(response => response.json())
        .then(data => {
            data.forEach(tree => {
                let marker = L.marker([tree.latitude, tree.longitude], { icon: defaultIcon })
                    .addTo(map)
                    .bindPopup(
                        `<strong>Tree Info</strong><br>
                        Code: ${tree.code}<br>
                        Age: ${tree.age} years<br>
                        Height: ${tree.height} m<br>
                        Stem Diameter: ${tree.stem_diameter} cm<br>
                        Canopy Diameter: ${tree.canopy_diameter} m`
                    );

                markers[tree.code.toUpperCase()] = marker;

                marker.on('click', function() {
                    setActiveMarker(marker);
                });
            });
        })
        .catch(error => console.error('Error loading tree data:', error));

    function setActiveMarker(marker) {
        if (activeMarker) {
            activeMarker.setIcon(defaultIcon);
        }
        activeMarker = marker;
        activeMarker.setIcon(activeIcon);
        map.setView(activeMarker.getLatLng(), 18);
        activeMarker.openPopup();
    }

    function searchTree() {
        var code = document.getElementById('treeCode').value.trim().toUpperCase();
        var messageDiv = document.getElementById('search-message');

        if (markers[code]) {
            setActiveMarker(markers[code]);
            messageDiv.innerHTML = '';
        } else {
            messageDiv.innerHTML = 'Tree not found on the map';
        }
    }
</script>
@endsection
