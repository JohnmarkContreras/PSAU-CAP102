<table class="w-full text-sm text-left border border-gray-200 rounded-lg mt-2">
    <thead class="bg-gray-100">
        <tr>
            <th class="px-4 py-2 border">Tree Code</th>
            <th class="px-4 py-2 border">Harvest Date</th>
            <th class="px-4 py-2 border">Weight (kg)</th>
            <th class="px-4 py-2 border">Quality</th>
            <th class="px-4 py-2 border">Notes</th>
        </tr>
    </thead>
    <tbody>
        @forelse($harvests as $harvest)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border">{{ $harvest->tree->code ?? 'N/A' }}</td>
                <td class="px-4 py-2 border">{{ \Carbon\Carbon::parse($harvest->harvest_date)->format('M d, Y') }}</td>
                <td class="px-4 py-2 border">{{ $harvest->harvest_weight_kg }}</td>
                <td class="px-4 py-2 border">{{ $harvest->quality }}</td>
                <td class="px-4 py-2 border">{{ $harvest->notes ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-gray-500 py-6">
                    No harvest records available for this selection.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
