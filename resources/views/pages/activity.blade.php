@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Activity Logs">
            <div class="text-sm text-black/90 space-y-0.5">
                <div class="overflow-x-auto">
                    <table id="activityLogsTable" class="min-w-full text-sm">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td>{{ $log->user->name ?? 'System' }}</td>
                                    <td>{{ $log->action }}</td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
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
