@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Activity logs">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-600 border border-gray-200 rounded-lg overflow-hidden">
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

                        {{-- CUSTOM PAGINATION --}}
                        @if ($logs->lastPage() > 1)
                            <div class="mt-6 flex justify-center">
                                <ul class="inline-flex items-center space-x-1">
                                    {{-- Prev Button --}}
                                    @if ($logs->onFirstPage())
                                        <li><span class="px-3 py-2 bg-gray-200 text-gray-400 rounded-l-lg">Prev</span></li>
                                    @else
                                        <li><a href="{{ $logs->previousPageUrl() }}" class="px-3 py-2 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100">Prev</a></li>
                                    @endif

                                    {{-- Page Numbers (1–9 only) --}}
                                    @for ($i = 1; $i <= min(9, $logs->lastPage()); $i++)
                                        @if ($i == $logs->currentPage())
                                            <li><span class="px-3 py-2 bg-blue-500 text-white border border-blue-500">{{ $i }}</span></li>
                                        @else
                                            <li><a href="{{ $logs->url($i) }}" class="px-3 py-2 bg-white border border-gray-300 hover:bg-gray-100">{{ $i }}</a></li>
                                        @endif
                                    @endfor

                                    {{-- Next Button --}}
                                    @if ($logs->hasMorePages())
                                        <li><a href="{{ $logs->nextPageUrl() }}" class="px-3 py-2 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100">Next</a></li>
                                    @else
                                        <li><span class="px-3 py-2 bg-gray-200 text-gray-400 rounded-r-lg">Next</span></li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                </x-card>
        </section>
    </main>
@endsection
