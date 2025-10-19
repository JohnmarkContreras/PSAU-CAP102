@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Activity Logs">
            <div class="text-sm text-black/90 space-y-0.5">
                <div class="overflow-x-auto">
                    <table id="activityLogsTable" 
                        class="min-w-full text-sm text-left text-gray-600 border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-50 text-gray-700 uppercase text-xs font-semibold">
                            <tr>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Activity</th>
                                <th class="px-6 py-3">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $log->causer->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $log->description }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $log->created_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                        No activity logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-card>
    </section>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#activityLogsTable').DataTable({
            responsive: true,
            pageLength: 10,
            ordering: true,
            order: [[2, 'desc']], // Sort by "Time" descending
            columnDefs: [
                { orderable: false, targets: [] } // all columns sortable
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search logs...",
                lengthMenu: "Show _MENU_ entries",
                paginate: {
                    previous: "← Prev",
                    next: "Next →"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ logs",
                infoEmpty: "No logs available",
            }
        });
    });
</script>
@endpush
