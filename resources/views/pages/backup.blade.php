@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Backup')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Backup">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <a href="{{ route('dead-tree-requests.index') }}" class="text-blue-600 hover:underline font-medium">
                            View pending dead tree for approval
                    </a>
                    </div>
                </x-card>
        </section>
    </main>
@endsection
