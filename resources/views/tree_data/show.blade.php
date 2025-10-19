@extends('layouts.app')

@section('title', 'Tree Data')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
    <x-card :title="'Tree Data — ID: ' . ($treeData->id ?? 'N/A')">
        <div class="text-sm text-black/90">
        <p><strong>Code:</strong> {{ optional($treeData->treeCode)->code ?? '—' }}</p>
        <p><strong>DBH (in):</strong> {{ $treeData->dbh ?? '—' }}</p>
        <p><strong>DBH (cm):</strong> {{ number_format(($treeData->dbh ?? 0) * 2.54, 2) }}</p>
        <p><strong>Height (m):</strong> {{ $treeData->height ?? '—' }}</p>
        <p><strong>Age:</strong> {{ $treeData->age ?? '—' }}</p>
        <p><strong>Estimated Biomass (kg):</strong> {{ $treeData->estimated_biomass_kg ?? '—' }}</p>
        <p><strong>Carbon Stock (kg C):</strong> {{ $treeData->carbon_stock_kg ?? '—' }}</p>
        <p><strong>Annual Sequestration (kg CO₂/yr):</strong> {{ $treeData->annual_sequestration_kgco2 ?? '—' }}</p>
        <p class="mt-4">
            <a href="{{ route('tree_data.sequestered') }}" class="text-green-700 hover:underline">Back to list</a>
        </p>
        </div>
    </x-card>
    </section>
</main>
@endsection