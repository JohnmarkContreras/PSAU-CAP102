@extends('layouts.app')

@section('title', 'Imported Tamarind Tree Locations')
@section('content')
<!-- Leaflet core -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- MarkerCluster plugin -->
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>


<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Imported Tamarind Tree Locations">
            <div class="flex items-center justify-between mb-4">
                <!-- Search Box -->
                <div class="flex flex-col w-full max-w-md">
                    <div class="relative">
                        <input type="text" id="treeCode" 
                            placeholder="Enter Tree Code (e.g., SOUR101)" 
                            class="border px-3 py-2 rounded text-sm w-full focus:ring focus:ring-green-300"
                            onkeyup="showSuggestions(this.value)">
                        <ul id="suggestions" 
                            class="absolute z-20 bg-white border w-full rounded shadow mt-1 hidden max-h-40 overflow-y-auto"></ul>
                    </div>
                    <div id="search-message" class="mt-1 text-red-600 text-sm font-semibold"></div>
                </div>

                <!-- Action Buttons -->
                <a href="{{ route('tree-images.create') }}" 
                    class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700">
                    + Add Tree
                </a>
            </div>

            <!-- Map -->
            <div id="map" class="h-[500px] w-full rounded z-0"></div>

        <!-- Tree Details Modal -->
        <div id="tree-details"
            class="absolute inset-0 z-50 flex justify-center items-start overflow-y-auto sm:min-w-full sm:m-h-full backdrop-blur-sm bg-white/30 hidden">
            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md border border-gray-300 mx-2 my-8 flex flex-col max-h-[90vh] pt-[env(safe-area-inset-top)]">
                
                <!-- Header with close button -->
                <div class="flex justify-between items-center sticky top-0 bg-white border-b p-2 z-10">
                    <h1 class="text-xl font-bold">Tree Details</h1>
                    <button onclick="closeTreeDetails()"
                            class="text-green-600 hover:text-red-600 text-xl font-bold px-3 py-2">
                        close
                    </button>
                </div>

                    <!-- Scrollable body -->
                    <div class="p-4 sm:p-6 overflow-y-auto">
                        <a href="{{ route('tree_data.create') }}"
                            class="text-green-700 hover:underline block mb-2"
                            id="edit-tree-link">
                            Add Tamarind Tree Data
                        </a>

                        <p><strong>Code:</strong> <span id="detail-code"></span></p>
                        <p class="hidden"><strong>Filename:</strong> <span id="detail-filename"></span></p>
                        <p><strong>Taken At:</strong> <span id="detail-taken"></span></p>

                        <div class="mt-4">
                            <img id="detail-image" src="" alt="Tree Image" class="rounded shadow max-h-64 hidden mx-auto">
                        </div>

                        <!-- Harvest Records Table -->
                        <div class="mt-6">
                            <h2 class="text-lg font-semibold mb-2">Harvest Records</h2>
                            <div class="overflow-x-auto">
                                <table class="w-full border border-gray-300 text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="border border-gray-300 px-2 py-1 text-left">Date</th>
                                            <th class="border border-gray-300 px-2 py-1 text-left">Weight (kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detail-harvests">
                                        <!-- JS will inject rows here -->
                                    </tbody>
                                </table>
                            </div>
                            <!-- Pagination controls -->
                            <div class="mt-2 flex justify-between items-center text-sm">
                                <button id="harvest-prev" class="px-2 py-1 bg-gray-200 rounded">Prev</button>
                                <span id="harvest-page-info"></span>
                                <button id="harvest-next" class="px-2 py-1 bg-gray-200 rounded">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    </section>
</main>

<script>
/* ========= Map, markers, lookup stores ========= */
var map = L.map('map').setView([15.2191, 120.6950], 17);

L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
attribution: 'Tiles &copy; Esri',
maxZoom: 19
}).addTo(map);

//GQIS
// // Example: same source QGIS might use:
// L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
//     attribution: '© OpenStreetMap contributors'
// }).addTo(map);

const clusterGroup = L.markerClusterGroup({
showCoverageOnHover: false,
disableClusteringAtZoom: 19
});
map.addLayer(clusterGroup);

var treeData = {};   // keyed by UPPERCASE code for fast lookup
var markers = {};    // keyed by UPPERCASE code
var activeMarker = null;

var defaultIcon = new L.Icon({
iconUrl: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
iconSize: [25, 41],
iconAnchor: [12, 41],
popupAnchor: [1, -34]
});
var activeIcon = new L.Icon({
iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
iconSize: [25, 41],
iconAnchor: [12, 41],
popupAnchor: [1, -34]
});

/* ========= Fetch markers for visible bounds (debounced) ========= */
let fetchTimeout = null;
let lastBoundsKey = null;

function boundsKey(b) {
return [b.getSouthWest().lat, b.getSouthWest().lng, b.getNorthEast().lat, b.getNorthEast().lng].join(',');
}

function fetchMarkersForBounds() {
const b = map.getBounds();
const key = boundsKey(b);
if (key === lastBoundsKey) return;
lastBoundsKey = key;

const params = new URLSearchParams({
    south: b.getSouthWest().lat,
    west: b.getSouthWest().lng,
    north: b.getNorthEast().lat,
    east: b.getNorthEast().lng,
    limit: 1000
});

fetch(`/tree-images/data?${params.toString()}`)
    .then(r => r.json())
    .then(data => {
    clusterGroup.clearLayers();
    addMarkers(data);
    })
    .catch(err => console.error('Error loading tree data:', err));
}

function debouncedFetch() {
if (fetchTimeout) clearTimeout(fetchTimeout);
fetchTimeout = setTimeout(fetchMarkersForBounds, 250);
}

map.on('load', debouncedFetch);
map.on('moveend', debouncedFetch);
debouncedFetch();

/* ========= Live location tracking ========= */
var userMarker = null;
var userCircle = null;

if (navigator.geolocation) {
navigator.geolocation.watchPosition(
    (pos) => {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    const accuracy = pos.coords.accuracy;

    // If marker doesn't exist yet, create it
    if (!userMarker) {
        userMarker = L.marker([lat, lng], {
        icon: new L.Icon({
            iconUrl: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
        })
        }).addTo(map).bindPopup("You are here");


        map.setView([lat, lng], 17); // center map on first fix
    } else {
        // Update marker and circle
        userMarker.setLatLng([lat, lng]);
    }
    },
    (err) => {
    console.error("Geolocation error:", err);
    },
    {
    enableHighAccuracy: true,
    maximumAge: 10000,
    timeout: 10000
    }
);
} else {
console.warn("Geolocation is not supported by this browser.");
}

/* ========= Add markers and keep data for lookup ========= */
function addMarkers(data) {
    data.forEach(tree => {
        const originalCode = tree.code || '';
        const codeKey = String(originalCode).toUpperCase();

        treeData[codeKey] = tree;

        const popupHtml = `<strong>${escapeHtmlAttr(originalCode)}</strong><br>
        <a href="#" class="popup-details" data-code="${escapeHtmlAttr(originalCode)}" data-treeid="${escapeHtmlAttr(tree.id)}">Details</a>`;

        const marker = L.marker([tree.latitude, tree.longitude], { icon: defaultIcon })
        .bindPopup(popupHtml);

        clusterGroup.addLayer(marker);
        markers[codeKey] = marker;

        marker.on('click', () => setActiveMarker(marker, tree));
    });
}

function setActiveMarker(marker, tree) {
if (activeMarker) activeMarker.setIcon(defaultIcon);
activeMarker = marker;
activeMarker.setIcon(activeIcon);
map.setView(activeMarker.getLatLng(), 18);
activeMarker.openPopup();
showTreeDetails(tree);
}

/* ========= Utility ========= */
function escapeHtmlAttr(s) {
if (s == null) return '';
return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
    .replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
function qs(id) { return document.getElementById(id); }

/* ========= Modal / detail UI ========= */
    let currentPage = 1;
    const pageSize = 5;
    let currentHarvests = [];

    function renderHarvests() {
    const harvestsBody = qs('detail-harvests');
    const pageInfo = qs('harvest-page-info');
    if (!harvestsBody) return;

    harvestsBody.innerHTML = '';

    const totalPages = Math.ceil(currentHarvests.length / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageItems = currentHarvests.slice(start, end);

    pageItems.forEach(h => {
        const row = document.createElement('tr');
        row.innerHTML = `
        <td class="border border-gray-300 px-2 py-1">${h.date}</td>
        <td class="border border-gray-300 px-2 py-1">${h.weight}</td>
        `;
        harvestsBody.appendChild(row);
    });

    if (pageInfo) {
        pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    }

    qs('harvest-prev').disabled = currentPage <= 1;
    qs('harvest-next').disabled = currentPage >= totalPages;
    }

    function showTreeDetails(tree) {
    if (!tree) return console.error('showTreeDetails: tree is null/undefined');

    qs('detail-code').innerText = tree.code || '';
    qs('detail-filename').innerText = tree.filename || '';
    qs('detail-taken').innerText = tree.taken_at ?? '—';

    const img = qs('detail-image');
    if (img) {
        if (tree.image_path) {
        img.src = tree.image_path;
        img.classList.remove('hidden');
        } else if (tree.filename) {
        img.src = `/storage/tree_images/${tree.filename}`;
        img.classList.remove('hidden');
        } else {
        img.src = '';
        img.classList.add('hidden');
        }
    }

    // reset pagination state
    currentPage = 1;
    currentHarvests = tree.harvests || [];
    renderHarvests();

    // prepare edit link
    prepareEditLink(tree);

    // show modal
    const modal = qs('tree-details');
    modal.classList.remove('hidden');
    modal.style.pointerEvents = 'auto';
    }

    // Hook up pagination buttons once
    document.addEventListener('DOMContentLoaded', () => {
    qs('harvest-prev').addEventListener('click', () => {
        if (currentPage > 1) {
        currentPage--;
        renderHarvests();
        }
    });
    qs('harvest-next').addEventListener('click', () => {
        const totalPages = Math.ceil(currentHarvests.length / pageSize);
        if (currentPage < totalPages) {
        currentPage++;
        renderHarvests();
        }
    });
    });


function prepareEditLink(tree) {
    const baseUrl = "{{ route('tree_data.create') }}"; 
    const code = tree.code ? encodeURIComponent(tree.code) : '';
    const id = tree.tree_code_id || tree.id || '';
    const href = `${baseUrl}?tree_code=${code}&tree_code_id=${id}`;

    const link = document.getElementById('edit-tree-link');
    if (!link) return;

    link.href = href;
    link.onclick = function (e) {
        e.preventDefault();

        //  Completely remove modal + backdrop
        const modal = document.getElementById('tree-details');
        if (modal) modal.remove();

        document.querySelectorAll('.modal-backdrop, .backdrop').forEach(el => el.remove());

        //  Redirect cleanly to form
        setTimeout(() => { window.location.href = href; }, 50);
    };
}


function closeTreeDetails() {
const modal = qs('tree-details');
if (modal) {
    modal.classList.add('hidden');
    modal.style.pointerEvents = ''; // reset
}
document.querySelectorAll('.modal-backdrop, .backdrop').forEach(el => el.remove());
}

/* ========= Delegated popup/details click ========= */
document.addEventListener('click', function (ev) {
const btn = ev.target.closest('.popup-details, .open-tree-details');
if (!btn) return;

ev.preventDefault();

const codeAttr = btn.dataset.code || null;
const idAttr = btn.dataset.treeid || btn.dataset.id || null;

let tree = null;
if (codeAttr) {
    tree = treeData[codeAttr.toUpperCase()] || null;
}
if (!tree && idAttr) {
    tree = Object.values(treeData).find(t => String(t.id) === String(idAttr)) || null;
}
if (!tree) {
    tree = {
    code: codeAttr,
    id: idAttr,
    tree_code_id: idAttr,
    filename: btn.dataset.filename || null,
    image_path: btn.dataset.image || null,
    taken_at: btn.dataset.taken || null
    };
}

console.log('Delegated trigger clicked, resolved tree:', tree);
showTreeDetails(tree);
}, false);

/* ========= Search suggestions ========= */
let allCodes = [];
fetch('/tree-images/codes')
.then(r => r.json())
.then(codes => { allCodes = codes.map(c => String(c).toUpperCase()); })
.catch(err => console.error('Error loading codes:', err));

function showSuggestions(query) {
const suggestionsBox = qs('suggestions');
if (!suggestionsBox) return;
suggestionsBox.innerHTML = '';

if (!query || query.length < 1) {
    suggestionsBox.classList.add('hidden'); return;
}

const matches = allCodes.filter(c => c.includes(query.toUpperCase())).slice(0, 10);
if (matches.length === 0) { suggestionsBox.classList.add('hidden'); return; }

matches.forEach(code => {
    const li = document.createElement('li');
    li.className = 'px-3 py-2 hover:bg-green-100 cursor-pointer text-sm';
    li.textContent = code;
    li.onclick = function () {
    qs('treeCode').value = code;
    suggestionsBox.classList.add('hidden');
    try {
        if (markers[code]) setActiveMarker(markers[code], treeData[code]);
        else {
        fetch(`/tree-images/data?code=${code}`)
            .then(r => r.json())
            .then(data => {
            if (data.length > 0) {
                addMarkers(data);
                const k = (data[0].code || '').toUpperCase();
                if (markers[k]) setActiveMarker(markers[k], treeData[k]);
            }
            });
        }
    } catch (err) { console.warn('Suggestion click: missing map helpers', err); }
    };
    suggestionsBox.appendChild(li);
});

suggestionsBox.classList.remove('hidden');
}

/* ========= Page ready ========= */
document.addEventListener('DOMContentLoaded', function () {
const editLink = qs('edit-tree-link');
if (editLink && !editLink.getAttribute('href')) {
    editLink.href = "{{ route('tree_data.create') }}";
}
});
</script>

@endsection
