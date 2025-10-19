@extends('layouts.app')
@section('title', 'Edit Tree Data')
@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-white rounded-lg shadow-md p-6 relative">
        <x-card title="Edit Tamarind Tree Data">

            <!-- Back button -->
            <div class="flex justify-end mb-4">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                    ← Back
                </a>
            </div>

            <!-- Form -->
            <form id="editTreeForm" action="{{ route('tree_data.update', $tree->tree_code_id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <!-- Tree Code -->
                <div>
                    <label for="tree_code_id" class="block text-sm font-medium text-gray-700">ID</label>
                    <input type="text" name="tree_code_id" id="tree_code_id"
                           value="{{ old('tree_code_id', $tree->tree_code_id) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- DBH -->
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700">DBH (cm)</label>
                    <input type="number" step="0.01" name="dbh" id="dbh"
                           value="{{ old('dbh', $tree->dbh) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700">Height (m)</label>
                    <input type="number" step="0.01" name="height" id="height"
                           value="{{ old('height', $tree->height) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700">Age (years)</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $tree->age) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Stem Diameter -->
                <div>
                    <label for="stem_diameter" class="block text-sm font-medium text-gray-700">Stem Diameter (cm)</label>
                    <input type="number" step="0.01" name="stem_diameter" id="stem_diameter"
                           value="{{ old('stem_diameter', $tree->stem_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Canopy Diameter -->
                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700">Canopy Diameter (m)</label>
                    <input type="number" step="0.01" name="canopy_diameter" id="canopy_diameter"
                           value="{{ old('canopy_diameter', $tree->canopy_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Update Tree Data
                    </button>
                </div>
            </form>
        </x-card>
    </section>
</main>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Confirm Update</h2>
        <p class="text-gray-600 mb-6">Are you sure you want to update this tree data? This action cannot be undone.</p>
        <div class="flex gap-3">
            <button id="confirmBtn" class="flex-1 rounded-lg bg-green-600 text-white py-2 hover:bg-green-700 transition font-medium">
                Yes, Update
            </button>
            <button id="cancelBtn" class="flex-1 rounded-lg border border-gray-300 text-gray-700 py-2 hover:bg-gray-50 transition">
                Cancel
            </button>
        </div>

        @extends('layouts.app')
@section('title', 'Edit Tree Data')
@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-white rounded-lg shadow-md p-6 relative">
        <x-card title="Edit Tamarind Tree Data">

            <!-- Back button -->
            <div class="flex justify-end mb-4">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                    ← Back
                </a>
            </div>

            <!-- Form -->
            <form id="editTreeForm" action="{{ route('tree_data.update', $tree->tree_code_id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <!-- Tree Code -->
                <div>
                    <label for="tree_code_id" class="block text-sm font-medium text-gray-700">ID</label>
                    <input type="text" name="tree_code_id" id="tree_code_id"
                           value="{{ old('tree_code_id', $tree->tree_code_id) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- DBH -->
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700">DBH (cm)</label>
                    <input type="number" step="0.01" name="dbh" id="dbh"
                           value="{{ old('dbh', $tree->dbh) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700">Height (m)</label>
                    <input type="number" step="0.01" name="height" id="height"
                           value="{{ old('height', $tree->height) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700">Age (years)</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $tree->age) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Stem Diameter -->
                <div>
                    <label for="stem_diameter" class="block text-sm font-medium text-gray-700">Stem Diameter (cm)</label>
                    <input type="number" step="0.01" name="stem_diameter" id="stem_diameter"
                           value="{{ old('stem_diameter', $tree->stem_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Canopy Diameter -->
                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700">Canopy Diameter (m)</label>
                    <input type="number" step="0.01" name="canopy_diameter" id="canopy_diameter"
                           value="{{ old('canopy_diameter', $tree->canopy_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Update Tree Data
                    </button>
                </div>
            </form>
        </x-card>
    </section>
</main>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Confirm Update</h2>
        <p class="text-gray-600 mb-6">Are you sure you want to update this tree data? This action cannot be undone.</p>
        <div class="flex gap-3">
            <button id="confirmBtn" class="flex-1 rounded-lg bg-green-600 text-white py-2 hover:bg-green-700 transition font-medium">
                Yes, Update
            </button>
            <button id="cancelBtn" class="flex-1 rounded-lg border border-gray-300 text-gray-700 py-2 hover:bg-gray-50 transition">
                Cancel
            </button>
      @extends('layouts.app')
@section('title', 'Edit Tree Data')
@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-white rounded-lg shadow-md p-6 relative">
        <x-card title="Edit Tamarind Tree Data">

            <!-- Back button -->
            <div class="flex justify-end mb-4">
                <a href="{{ url()->previous() }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                    ← Back
                </a>
            </div>

            <!-- Form -->
            <form id="editTreeForm" action="{{ route('tree_data.update', $tree->tree_code_id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')
                <!-- Tree Code -->
                <div>
                    <label for="tree_code_id" class="block text-sm font-medium text-gray-700">ID</label>
                    <input type="text" name="tree_code_id" id="tree_code_id"
                           value="{{ old('tree_code_id', $tree->tree_code_id) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- DBH -->
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700">DBH (cm)</label>
                    <input type="number" step="0.01" name="dbh" id="dbh"
                           value="{{ old('dbh', $tree->dbh) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700">Height (m)</label>
                    <input type="number" step="0.01" name="height" id="height"
                           value="{{ old('height', $tree->height) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700">Age (years)</label>
                    <input type="number" name="age" id="age"
                           value="{{ old('age', $tree->age) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Stem Diameter -->
                <div>
                    <label for="stem_diameter" class="block text-sm font-medium text-gray-700">Stem Diameter (cm)</label>
                    <input type="number" step="0.01" name="stem_diameter" id="stem_diameter"
                           value="{{ old('stem_diameter', $tree->stem_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Canopy Diameter -->
                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700">Canopy Diameter (m)</label>
                    <input type="number" step="0.01" name="canopy_diameter" id="canopy_diameter"
                           value="{{ old('canopy_diameter', $tree->canopy_diameter) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Update Tree Data
                    </button>
                </div>
            </form>
        </x-card>
    </section>
</main>

<!-- Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Confirm Update</h2>
        <p class="text-gray-600 mb-6">Are you sure you want to update this tree data? This action cannot be undone.</p>
        <div class="flex gap-3">
            <button id="confirmBtn" class="flex-1 rounded-lg bg-green-600 text-white py-2 hover:bg-green-700 transition font-medium">
                Yes, Update
            </button>
            <button id="cancelBtn" class="flex-1 rounded-lg border border-gray-300 text-gray-700 py-2 hover:bg-gray-50 transition">
                Cancel
            </button>
        </div>
    </div>
</div>
    </div>
</div>
