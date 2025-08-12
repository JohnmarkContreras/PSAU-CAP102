@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6 bg-white shadow-lg rounded-xl">
    <h1 class="text-2xl font-bold mb-4">Activity Logs</h1>

    <table class="w-full table-auto border-collapse">
        <thead>
            <tr class="bg-gray-100 text-left">
                <th class="px-4 py-2">User</th>
                <th class="px-4 py-2">Activity</th>
                <th class="px-4 py-2">Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">
                        {{ $log->causer->name ?? 'System' }}
                    </td>
                    <td class="px-4 py-2">
                        {{ $log->description }}
                    </td>
                    <td class="px-4 py-2">
                        {{ $log->created_at->diffForHumans() }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">
        {{ $logs->links() }}
    </div>
</div>
@endsection
