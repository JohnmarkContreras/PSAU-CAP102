@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Farm Data')

@section('content')
    <h1 class="font-extrabold text-lg mb-6">
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
            <h2 class="text-[#0b5a0b] font-extrabold text-2xl mb-2 border-l-4 border-[#0b5a0b] pl-3">
                <x-card title="Import tree data">
                    <div class="text-sm text-black/90 space-y-0.5">
                        @if(session('success'))
                            <div class="bg-green-100 p-2 text-green-700 rounded">{{ session('success') }}</div>
                        @endif

                        <form action="/trees/import" method="POST" enctype="multipart/form-data" class="p-4 bg-white rounded shadow">
                            @csrf
                            <input type="file" name="file" class="mb-2">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Import Excel</button>
                        </form>
                    </div>
                </x-card>
            </h2>
        </section>

    </main>
    </h1>
@endsection
