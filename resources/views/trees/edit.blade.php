
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
            <p class="text-sm text-gray-700 mb-4">Are you sure you want to report this tree as dead?</p>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="button" onclick="submitForm()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Confirm</button>
            </div>
        </div>
    </div>
@auth
    <form action="{{ route('trees.reportDead', $tree->code) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="mb-3">
            <label class="block font-medium">Code: {{$tree->code}}</label>

            <label class="block font-medium">Status</label>
            <select name="status" id="status-select" class="border rounded p-2 w-full" required>
                <option value="dead" {{ $tree->status === 'dead' ? 'selected' : '' }}>Dead</option>
            </select>
        </div>

        <div id="dead-fields" class="{{ $tree->status !== 'dead' ? 'hidden' : '' }}">
            <div class="mb-3">
                <label class="block font-medium">Reason</label>
                <textarea name="reason" class="border rounded p-2 w-full">{{ old('reason') }}</textarea>
            </div>

            <div class="mb-3 max-h-48 w-48">
                <label class="block font-medium">Image</label>
                <input type="file" name="image" id="image-input" class="border rounded p-2 w-full">
                <img id="image-preview" class="mt-2 rounded shadow max-h-48 hidden" />
            </div>

        </div>

        <button type="button" onclick="submitForm()" class="bg-green-600 text-white px-4 py-2 rounded">
            Update
        </button>

    </form>
    @else
    <div class="text-red-600">Please log in to report a dead tree.</div>
@endauth
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const statusSelect = document.getElementById('status-select');
        const deadFields = document.getElementById('dead-fields');

        function toggleDeadFields() {
            if (statusSelect.value === 'dead') {
                deadFields.classList.remove('hidden');
            } else {
                deadFields.classList.add('hidden');
            }
        }

        toggleDeadFields(); // Initial check
        statusSelect.addEventListener('change', toggleDeadFields);
    });

    //js for the modal confirmation
    function openModal() {
        document.getElementById('confirm-modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
    }

    function submitForm() {
        document.querySelector('form').submit();
    }


    //image-preview
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
