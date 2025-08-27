@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<main class="flex-1 p-6">
    <section class="bg-white rounded-2xl shadow p-6 max-w-xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">We value your feedback</h2>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('feedback.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block font-semibold">Type</label>
                <select name="type" class="w-full border rounded p-2">
                    <option value="Bug">Bug</option>
                    <option value="Suggestion">Suggestion</option>
                    <option value="Question">Question</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold">Rating</label>
                <select name="rating" class="w-full border rounded p-2">
                    <option value="">-- Select --</option>
                    @for($i=1;$i<=5;$i++)
                        <option value="{{ $i }}">{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="block font-semibold">Message</label>
                <textarea name="message" rows="4" class="w-full border rounded p-2" required></textarea>
            </div>

            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                Submit
            </button>
        </form>
    </section>
</main>
@endsection
