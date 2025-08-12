@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Feedback')

@section('content')
    <h1 class="font-extrabold text-lg mb-6">
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Feedback">
                    <div class="text-sm text-black/90 space-y-0.5">
                        
                    </div>
                </x-card>
            </h2>
        </section>

    </main>
    </h1>
@endsection
