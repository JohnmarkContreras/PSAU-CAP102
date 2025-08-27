@extends('layouts.app')

@section('title', 'User Feedback')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <x-card title="User feedbacks">
                <div class="text-sm text-black/90 space-y-0.5">
                    <div class="space-y-4">
                    @foreach($feedbacks as $feedback)
                        <div class="bg-white rounded-2xl shadow p-4 relative">
                            <!-- Status Label -->
                            <span class="
                                absolute top-2 right-2 text-xs font-semibold px-2 py-1 rounded-full
                                @if($feedback->status == 'New') bg-yellow-100 text-yellow-700
                                @elseif($feedback->status == 'In Review') bg-blue-100 text-blue-700
                                @elseif($feedback->status == 'Resolved') bg-green-100 text-green-700
                                @endif
                            ">
                                {{ $feedback->status }}
                            </span>

                            <p class="font-semibold">
                                {{ $feedback->user->name ?? 'Anonymous' }}: {{ $feedback->message }}
                            </p>
                            <p class="text-sm text-gray-500">{{ $feedback->created_at->diffForHumans() }}</p>
                            <p class="mt-2"><strong>Type:</strong> {{ $feedback->type }}</p>
                            <p><strong>Rating:</strong> {{ $feedback->rating ?? 'N/A' }}</p>

                            <form action="{{ route('feedback.updateStatus', $feedback) }}" method="POST" class="mt-3 flex items-center space-x-2">
                                @csrf
                                <label class="font-semibold text-sm">Change Status:</label>
                                <select name="status" class="border rounded p-1">
                                    <option value="New" {{$feedback->status=='New' ? 'selected':'' }}>New</option>
                                    <option value="In Review" {{$feedback->status=='In Review' ? 'selected':'' }}>In Review</option>
                                    <option value="Resolved" {{$feedback->status=='Resolved' ? 'selected':'' }}>Resolved</option>
                                </select>
                                <button class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Update</button>
                            </form>
                        </div>
                    @endforeach
            </x-card>
        </section>
    </main>
@endsection

