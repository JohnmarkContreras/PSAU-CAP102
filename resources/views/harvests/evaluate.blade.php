@extends('layouts.app') <!-- Inherit the layout -->

@section('title', 'Evaluation')

@section('content')
<main id="Prediction-evaluate" class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Evaluation">
            <div class="text-sm text-black/90 space-y-0.5">
                <h2 class="text-xl font-bold mb-4">Forecast Evaluation</h2>

                <ul class="mb-4">
                    <li>MAE: {{ number_format($evaluation['mae'], 2) }} kg</li>
                    <li>RMSE: {{ number_format($evaluation['rmse'], 2) }} kg</li>
                    <li>MAPE: {{ number_format($evaluation['mape'], 2) }}%</li>
                </ul>

                <h3 class="text-lg font-semibold mt-6">Season Totals ({{ $evaluation['season']['start'] }} → {{ $evaluation['season']['end'] }})</h3>
                <ul class="mb-4">
                    <li>Predicted Total: {{ $evaluation['season']['predicted_total'] }} kg</li>
                    <li>Actual Total: {{ $evaluation['season']['actual_total'] }} kg</li>
                    <li>Error: {{ $evaluation['season']['error'] }} kg</li>
                </ul>

                <table class="mt-4 border w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-2 py-1">Date</th>
                            <th class="border px-2 py-1">Predicted</th>
                            <th class="border px-2 py-1">Actual</th>
                            <th class="border px-2 py-1">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($evaluation['results'] as $row)
                            <tr>
                                <td class="border px-2 py-1">{{ $row['date'] }}</td>
                                <td class="border px-2 py-1">{{ $row['predicted'] }}</td>
                                <td class="border px-2 py-1">{{ $row['actual'] }}</td>
                                <td class="border px-2 py-1">{{ $row['predicted'] - $row['actual'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </section>
</main>
@endsection