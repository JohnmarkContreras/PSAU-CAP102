@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<main class="flex-1 p-6">
    <section class="bg-white rounded-2xl shadow p-8 max-w-xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center">We Value Your Feedback</h2>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 border border-green-300 p-3 rounded-lg mb-4 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Alpine.js required for dynamic interactivity -->
        <form x-data="{ type: '' }" action="{{ route('feedback.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Feedback Type -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Type</label>
                <select name="type" x-model="type" class="w-full border-gray-300 rounded-lg p-2 focus:ring-green-400 focus:border-green-400">
                    <option value="" disabled selected>-- Select Type --</option>
                    <option value="Bug">Bug</option>
                    <option value="Suggestion">Suggestion</option>
                    <option value="Question">Question</option>
                    <option value="Rate">Rate</option>
                </select>
            </div>

            <!-- Rating (Only visible if "Rate" is selected) -->
            <div x-show="type === 'Rate'" x-transition>
                <label class="block font-semibold text-gray-700 mb-1">Rating</label>
                <select name="rating" class="w-full border-gray-300 rounded-lg p-2 focus:ring-green-400 focus:border-green-400">
                    <option value="">-- Select Rating --</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>

            <!-- Message -->
            <div>
                <label class="block font-semibold text-gray-700 mb-1">Message</label>
                <textarea name="message" rows="4" class="w-full border-gray-300 rounded-lg p-2 focus:ring-green-400 focus:border-green-400" required></textarea>
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition">
                    Submit Feedback
                </button>
            </div>
        </form>
    </section>
</main>
@endsection

@push('scripts')
<!-- Alpine.js for dynamic interactivity (if not already included in your layout) -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
