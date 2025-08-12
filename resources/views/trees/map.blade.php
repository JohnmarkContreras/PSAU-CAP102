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
    <h1 class="font-extrabold text-lg mb-6">
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Tamarind tree location">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        // Initialize map view
        var map = L.map('map').setView([15.21912622129279, 120.69502532729408], 17);

        /// Use Esri World Imagery (Satellite View)
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye',
        maxZoom: 19,
    }).addTo(map);

        // Fetch tree data from server
        fetch('/trees/data')
            .then(response => response.json())
            .then(data => {
                data.forEach(tree => {
                    L.marker([tree.latitude, tree.longitude])
                        .addTo(map)
                        .bindPopup(
                            `<strong>Tree Info</strong><br>
                            Age: ${tree.age} years<br>
                            Height: ${tree.height} m<br>
                            Stem Diameter: ${tree.stem_diameter} cm<br>
                            Canopy Diameter: ${tree.canopy_diameter} m`
                        );
                });
            })
            .catch(error => console.error('Error loading tree data:', error));
    </script>
                    </div>
                </x-card>
            </h2>
        </section>

    </main>
    </h1>
@endsection
