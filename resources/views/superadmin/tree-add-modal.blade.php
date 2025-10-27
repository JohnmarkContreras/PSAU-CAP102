<!-- resources/views/superadmin/tree-add-modal.blade.php -->
<!-- <div class="modal fade" id="addTreeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered max-w-lg">
    <div class="modal-content p-6 rounded-xl shadow-lg bg-white">
      <h5 class="text-xl font-semibold mb-4 text-gray-800">Add New Tamarind Tree</h5>

      <form id="addTreeForm" method="POST" action="{{ route('superadmin.storeTreeData') }}">
        @csrf
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Tree Code</label>
            <input type="text" name="tree_code" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Age (years)</label>
            <input type="number" name="age" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Height (meters)</label>
            <input type="number" step="0.01" name="height" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Diameter (cm)</label>
            <input type="number" step="0.01" name="diameter" class="w-full border border-gray-300 rounded-lg px-3 py-2" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Latitude</label>
            <input type="text" name="latitude" id="latitude" class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Longitude</label>
            <input type="text" name="longitude" id="longitude" class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
          </div>

          <button type="button" onclick="fillLocation(true)" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
            Use Current Location
          </button>
        </div>

        <div class="flex justify-end mt-5 space-x-3">
          <button type="button" class="bg-gray-400 text-white px-4 py-2 rounded-lg" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="bg-green-700 text-white px-4 py-2 rounded-lg hover:bg-green-800">Save Tree</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function fillLocation(force = false) {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser.");
    return;
  }

  navigator.geolocation.getCurrentPosition(position => {
    document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
    document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
  }, () => {
    alert("Unable to retrieve your location.");
  });
}
</script> -->
