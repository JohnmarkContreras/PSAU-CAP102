@extends('layouts.app')

@section('title', 'Farm Data')
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Archived Users">
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-200 text-gray-800 px-4 py-1 mb-4 rounded cursor-pointer" onclick="history.back()">Back</button>
                </div>
            <div class="text-sm text-black/90 space-y-0.5">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($archives->isEmpty())
                    <div class="card">
                        <div class="card-body">No archived users found.</div>
                    </div>
                @else
                        <table class="hidden sm:table w-full bg-white border rounded">
                            <thead class="bg-gray-100">
                                <tr class="text-center">
                                    <th class="px-3 py-2">ID</th>
                                    <th class="px-3 py-2">Archived At</th>
                                    <th class="px-3 py-2">Name</th>
                                    <th class="px-3 py-2">Email</th>
                                    <th class="px-3 py-2">Username</th>
                                    <th class="px-3 py-2">Archived By</th>
                                    <th class="px-3 py-2" colspan="2">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($archives as $archive)
                                    @php
                                        $payload = json_decode($archive->payload, true) ?? [];
                                        $displayName = $payload['name'] ?? $payload['username'] ?? ($archive->username ?? '—');
                                        $displayEmail = $archive->email ?? $payload['email'] ?? '—';
                                        $displayUsername = $archive->username ?? $payload['username'] ?? ($payload['name'] ?? '—');
                                        $archivedAt = \Carbon\Carbon::parse($archive->archived_at)->toDayDateTimeString();
                                    @endphp

                                    <tr class="hover:bg-gray-50 text-center border-t">
                                        <td class="px-3 py-3 align-middle">{{ $archive->id }}</td>
                                        <td class="px-3 py-3 align-middle">{{ $archivedAt }}</td>
                                        <td class="px-3 py-3 align-middle">{{ $displayName }}</td>
                                        <td class="px-3 py-3 align-middle break-words">{{ $displayEmail }}</td>
                                        <td class="px-3 py-3 align-middle">{{ $displayUsername }}</td>
                                        <td class="px-3 py-3 align-middle">{{ $archive->archived_by ?? 'system' }}</td>

                                        <td class="px-3 py-3 align-middle">
                                            <a href="{{ route('user_archive.show', $archive->id) }}"
                                            class="inline-flex items-center px-3 py-1 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">
                                                View
                                            </a>
                                        </td>

                                        <td class="px-3 py-3 align-middle">
                                            <form action="{{ route('user_archive.restore', $archive->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Restore this archived user?');"
                                                class="inline-block">
                                                @csrf
                                                <input type="hidden" name="conflict_strategy" value="update">
                                                <button type="submit"
                                                        class="inline-flex items-center px-3 py-1 rounded bg-green-600 text-white text-xs hover:bg-green-700">
                                                    Restore
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($archives->hasPages())
                            <div class="pagination mt-4">
                                {{ $archives->withQueryString()->links('pagination::tailwind') }}
                            </div>
                        @endif
                    </div>
                @endif
            <!-- Mobile compact list (keeps desktop table as-is) -->
    <div class="sm:hidden space-y-4">
    @foreach ($archives as $archive)
        @php
        $payload = json_decode($archive->payload, true) ?? [];
        $displayName = $payload['name'] ?? $payload['username'] ?? ($archive->username ?? '—');
        $displayEmail = $archive->email ?? $payload['email'] ?? '—';
        $displayUsername = $archive->username ?? $payload['username'] ?? ($payload['name'] ?? '—');
        $archivedAtShort = \Carbon\Carbon::parse($archive->archived_at)->diffForHumans();
        @endphp

        <div class="w-full max-w-[360px] bg-white border rounded-lg p-3 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
            <div class="text-[11px] text-gray-500">ID • {{ $archive->id }} · <span class="text-gray-400">{{ $archivedAtShort }}</span></div>
            <div class="text-sm font-semibold truncate">{{ $displayName }}</div>
            <div class="text-xs text-gray-600 truncate break-words">{{ $displayEmail }}</div>

            </div>
        </div>

        <div class="mt-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
            <a href="{{ route('user_archive.show', $archive->id) }}"
                class="inline-flex items-center px-2 py-1 rounded bg-blue-600 text-white text-xs hover:bg-blue-700">
                View
            </a>

            <form action="{{ route('user_archive.restore', $archive->id) }}" method="POST" onsubmit="return confirm('Restore this archived user?');" class="inline-block">
                @csrf
                <input type="hidden" name="conflict_strategy" value="update">
                <button type="submit" class="inline-flex items-center px-2 py-1 rounded bg-green-600 text-white text-xs hover:bg-green-700">
                Restore
                </button>
            </form>
            </div>
        </div>
        </div>
    @endforeach
        </x-card>
    </section>
    </div>
</main>
@endsection