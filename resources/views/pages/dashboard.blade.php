@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-extrabold text-lg mb-6">
    <main id="dashboard-container" class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Dashboard">
                    <div class="text-xl text-black/90 space-y-0.5">
                        <p>Total Number of Trees:{{$totaltrees}}</p>
                        <p>Sour Trees: {{$totalsour}}</p>
                        <p>Sweet Trees: {{$totalsweet}}</p>
                        <p>Semi-Sweet Trees:{{$totalsemi_sweet}}</p>
                        <p>Harvest Schedule:</p>
                    </div>
                </x-card>
            </h2>
        </section>

        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Notification">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <p>Recent Activities:</p>
                        <p>Reminder:</p>
                        <p>Alert:</p>
                    </div>
                </x-card>
            </h2>
        </section>
        
    </main>
    </h1>
@endsection
