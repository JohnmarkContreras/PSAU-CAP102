@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-6 shadow-sm">
        <x-card title="Activity Logs">
            <div class="overflow-x-auto">
                <table id="activityLogsTable"
                       class="min-w-full border border-gray-200 rounded-lg text-sm text-left text-gray-700">
                    <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-3">User</th>
                            <th class="px-6 py-3">Activity</th>
                            <th class="px-6 py-3">Route / URL</th>
                            <th class="px-6 py-3 text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ optional($log->causer)->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $log->description }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 truncate max-w-xs">
                                    @php
                                        $props = is_array($log->properties) ? $log->properties : json_decode($log->properties ?? '{}', true);
                                        $route = $props['route'] ?? $props['url'] ?? '-';
                                    @endphp
                                    <span title="{{ $route }}">{{ $route }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-right whitespace-nowrap">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No activity logs found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </section>
</main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!$.fn.DataTable.isDataTable('#activityLogsTable')) {
        $('#activityLogsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[3, 'desc']], // sort by "Time"
            columnDefs: [
                { orderable: false, targets: [0, 1, 2] }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search logs...",
                lengthMenu: "Show _MENU_ entries",
                paginate: { previous: "← Prev", next: "Next →" },
                info: "Showing _START_ to _END_ of _TOTAL_ logs",
                infoEmpty: "No logs available",
            }
        });
    }
});
</script>
@endpush
