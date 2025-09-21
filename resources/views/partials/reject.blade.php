<!-- Trigger Button -->
<button type="button" onclick="document.getElementById('modal-{{ $geotag->id }}').classList.remove('hidden')"
        class="btn btn-success btn-sm p-2 w-20 rounded-xs mb-2 text-white cursor-pointer bg-red-600">
    Reject
</button>

<!-- Modal -->
<div id="modal-{{ $geotag->id }}" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <form action="{{ route('pending-geotags.reject', $geotag->id) }}" method="POST">
            @csrf
            @method('PATCH')

            <label for="rejection_reason_{{ $geotag->id }}" class="block text-sm font-medium text-gray-700">
                Reason
            </label>
            <textarea name="rejection_reason" id="rejection_reason_{{ $geotag->id }}" rows="3"
                    class="mt-1 block w-full border rounded-md shadow-sm"></textarea>

            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('modal-{{ $geotag->id }}').classList.add('hidden')"
                        class="px-3 py-1 bg-gray-300 rounded">
                    Cancel
                </button>
                <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded">
                    Confirm Reject
                </button>
            </div>
        </form>
    </div>
</div>
