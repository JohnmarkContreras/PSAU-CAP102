@extends('layouts.app')

@section('title', 'Pending Dead Tree Requests')

@section('content')
<div class="p-6 space-y-6">
    <x-card title="Pending Dead Tree Requests">
        @if($requests->isEmpty())
            <p class="text-gray-600">No pending requests at the moment.</p>
        @else
            <div class="grid gap-6">
                @foreach ($requests as $request)
                    <div class="border rounded p-4 shadow-sm bg-white">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="font-bold text-lg">Tree Code: {{ $request->tree_code }}</h3>
                            <span class="text-sm text-gray-500">Submitted by: {{ $request->user->name }}</span>
                        </div>

                        <p class="text-sm text-gray-700 mb-2"><strong>Reason:</strong> {{ $request->reason }}</p>

                        @if($request->image_path)
                            <img src="{{ asset('storage/' . $request->image_path) }}" class="max-w-xs rounded shadow mb-3" />
                        @endif

                        <div class="flex space-x-2">
                            <form action="{{ route('dead-tree-requests.approve', $request->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                    Approve
                                </button>
                            </form>

                            <form action="{{ route('dead-tree-requests.reject', $request->id) }}" method="POST">
                                @csrf
                                <button type="button" onclick="openRejectModal({{ $request->id }})"
                                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">
                {{ $requests->links() }} {{--  Laravel pagination --}}
            </div>
        @endif
    </x-card>
</div>
@endsection
