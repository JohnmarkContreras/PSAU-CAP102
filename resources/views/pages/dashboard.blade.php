@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Dashboard')

@section('content')
    <main id="dashboard-container" class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Dashboard">
                    <div class="text-l text-black/90 space-y-0.5">
                        <p>Total Number of Trees:{{$totaltrees}}</p>
                        <p>Sour Trees: {{$totalsour}}</p>
                        <p>Sweet Trees: {{$totalsweet}}</p>
                        <p>Semi-Sweet Trees:{{$totalsemi_sweet}}</p>
                        <p>Harvest Schedule:</p>
                    </div>
                </x-card>
        </section>

        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Notification">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="text-l text-black/90 space-y-0.5">
                            <h3 class="font-bold mb-2">Notifications</h3>
                            <ul class="space-y-2">
                                @forelse($notifications as $note)
                                    <li class="border-b pb-2">
                                        <p>{{ $note->data['message'] }}</p>
                                        <small class="text-gray-500">{{ $note->created_at->diffForHumans() }}</small>
                                    </li>
                                @empty
                                    <li>No notifications yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </x-card>
        </section>

        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Reminders">
                    <div class="text-l text-black/90 space-y-0.5">
                        
                    </div>
                </x-card>
        </section>
    </main>
@endsection
