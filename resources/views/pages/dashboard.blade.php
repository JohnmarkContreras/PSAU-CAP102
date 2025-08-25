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
                    <div class="text-l text-black/90 space-y-0.5">
                        <p>Recent Activities:</p>
                        <p>Reminder:</p>
                        <p>Alert:</p>
                    </div>
                </x-card>
        </section>
    </main>
@endsection
