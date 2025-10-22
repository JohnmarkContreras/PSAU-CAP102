@extends('layouts.app')

@section('title', 'Edit Tree Data')

@section('content')

<div class="min-h-screen bg-gray-50 py-4 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header with Back Button -->
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Edit Tree Data</h1>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center px-3 sm:px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                ← Back
            </a>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-md p-6 sm:p-8">
            <form id="editTreeForm" action="{{ route('tree_data.update', $tree->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Tree Code -->
                <div>
                    <label for="tree_code_id" class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                    <input type="text" name="tree_code_id" id="tree_code_id"
                           value="{{ old('tree_code_id', $tree->tree_code_id) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm"
                           required>
                </div>

                <!-- DBH -->
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700 mb-1">DBH (cm)</label>
                    <input type="number" step="0.01" name="dbh" id="dbh"
                           value="{{ old('dbh', $tree->dbh) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm"
                           required>
                </div>

                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700 mb-1">Height (m)</label>
                    <input type="number" step="0.01" name="height" id="height"
                           value="{{ old('height', $tree->height) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm"
                           required>
                </div>

                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700 mb-1">Age (years)</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $tree->age) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
                </div>

                <!-- Stem Diameter -->
                <div>
                    <label for="stem_diameter" class="block text-sm font-medium text-gray-700 mb-1">Stem Diameter (cm)</label>
                    <input type="number" step="0.01" name="stem_diameter" id="stem_diameter"
                           value="{{ old('stem_diameter', $tree->stem_diameter) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
                </div>

                <!-- Canopy Diameter -->
                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700 mb-1">Canopy Diameter (m)</label>
                    <input type="number" step="0.01" name="canopy_diameter" id="canopy_diameter"
                           value="{{ old('canopy_diameter', $tree->canopy_diameter) }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 shadow-sm focus:border-green-500 focus:ring-1 focus:ring-green-500 text-sm">
                </div>

                <!-- Submit Button -->
                <div class="pt-6">
                    <button type="submit"
                            class="w-full py-2 px-4 rounded-md text-white font-medium bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Update Tree Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Confirm Update</h2>
        <p class="text-gray-600 mb-6">Are you sure you want to update this tree data? This action cannot be undone.</p>
        <div class="flex gap-3">
            <button id="confirmBtn" class="flex-1 rounded-lg bg-green-600 text-white py-2 font-medium hover:bg-green-700 transition">
                Yes, Update
            </button>
            <button id="cancelBtn" class="flex-1 rounded-lg border border-gray-300 text-gray-700 py-2 font-medium hover:bg-gray-50 transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editTreeForm');
    const modal = document.getElementById('confirmModal');
    const confirmBtn = document.getElementById('confirmBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    // Show modal on form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        modal.classList.remove('hidden');
    });

    // Confirm and submit
    confirmBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
        form.submit();
    });

    // Cancel modal
    cancelBtn.addEventListener('click', function() {
        modal.classList.add('hidden');
    });

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

@endsection