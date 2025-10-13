@extends('layouts.app')

@section('title', 'User Feedback')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#f5f7f5] rounded-xl p-6 shadow-sm">
        <x-card title="User Feedbacks">
            <div class="space-y-6">
                @forelse($feedbacks as $feedback)
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition duration-300 p-6 relative">

                        <!-- Status Badge -->
                        <span class="
                            absolute top-4 right-4 text-xs font-semibold px-3 py-1 rounded-full
                            @if($feedback->status == 'New') bg-yellow-100 text-yellow-700
                            @elseif($feedback->status == 'In Review') bg-blue-100 text-blue-700
                            @elseif($feedback->status == 'Resolved') bg-green-100 text-green-700
                            @endif
                        ">
                            {{ $feedback->status }}
                        </span>

                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center rounded-full font-bold">
                                {{ strtoupper(substr($feedback->user->name ?? 'A', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $feedback->user->name ?? 'Anonymous' }}</p>
                                <p class="text-xs text-gray-500">{{ $feedback->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="border-t border-gray-100 pt-3">
                            <p class="text-gray-800 leading-relaxed">{{ $feedback->message ?? 'No message provided.' }}</p>
                        </div>

                        <!-- Metadata -->
                        <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
                            <p><span class="font-semibold text-gray-600">Type:</span> {{ $feedback->type }}</p>
                            <p><span class="font-semibold text-gray-600">Rating:</span>
                                @if($feedback->rating)
                                    <span class="text-yellow-500">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $feedback->rating)
                                                ★
                                            @else
                                                ☆
                                            @endif
                                        @endfor
                                    </span>
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>

                        <!-- Update Status Form -->
                        <form action="{{ route('feedback.updateStatus', $feedback) }}" method="POST" class="mt-5 flex flex-col sm:flex-row items-start sm:items-center sm:space-x-3 space-y-2 sm:space-y-0">
                            @csrf
                            <label class="font-medium text-gray-700 text-sm">Change Status:</label>
                            <select name="status" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none">
                                <option value="New" {{ $feedback->status == 'New' ? 'selected' : '' }}>New</option>
                                <option value="In Review" {{ $feedback->status == 'In Review' ? 'selected' : '' }}>In Review</option>
                                <option value="Resolved" {{ $feedback->status == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                            </select>
                            <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
                                Update
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-gray-600 py-10">No feedbacks available at the moment.</p>
                @endforelse
            </div>
        </x-card>
    </section>
</main>
@endsection
