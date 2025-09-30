@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Report Dead Tree</h2>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
            {{ session('success') }}
        </div>
    @endif

    <!-- Confirmation Modal -->
    <div id="confirm-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h2 class="text-lg font-bold mb-4">Confirm Submission</h2>
            <p class="text-sm text-gray-700 mb-4">Are you sure you want to submit this dead tree report for admin approval?</p>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="button" onclick="submitForm()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirm</button>
            </div>
        </div>
    </div>

@auth
    <form action="{{ route('dead-tree-requests.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="tree_code" value="{{ $tree->code }}">

        <div class="mb-3">
            <label class="block font-medium">Code:</label>
            <p class="text-sm text-gray-800">{{ $tree->code }}</p>

            <label class="block font-medium mt-2">Current Status:</label>
            <p class="text-sm text-gray-800">{{ ucfirst($tree->status) }}</p>
        </div>

        <div class="mb-3">
            <label class="block font-medium">Reason for Reporting</label>
            <textarea name="reason" class="border rounded p-2 w-full" required>{{ old('reason') }}</textarea>
        </div>

        <div class="mb-3 max-h-48 w-48">
            <label class="block font-medium">Upload Image</label>
            <input type="file" name="image" id="image-input" class="border rounded p-2 w-full" required>
            <img id="image-preview" class="mt-2 rounded shadow max-h-48 hidden" />
        </div>

        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
            Submit for Approval
        </button>
    </form>
@else
    <div class="text-red-600">Please log in to report a dead tree.</div>
@endauth
</div>

<script>
    function openModal() {
        document.getElementById('confirm-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }

    function submitForm() {
        document.querySelector('form').submit();
    }

    document.getElementById('image-input').addEventListener('change', function (e) {
        const preview = document.getElementById('image-preview');
        const file = e.target.files[0];

        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
        }
    });
</script>
@endsection
