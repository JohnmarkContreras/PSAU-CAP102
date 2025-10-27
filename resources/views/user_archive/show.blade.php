@extends('layouts.app')

@section('title', 'Archived User')

@section('content')
<main class="flex-1 p-6">
<section class="max-w-4xl mx-auto">
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b">
        <h1 class="text-lg font-semibold">Archived User</h1>
        <p class="text-sm text-gray-500 mt-1">
        {{ \Carbon\Carbon::parse($archive->archived_at)->toDayDateTimeString() }}
        </p>
    </div>

    <div class="p-6 space-y-6">
        <!-- summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gray-50 p-3 rounded">
            <div class="text-xs text-gray-500">User ID</div>
            <div class="font-medium">{{ $archive->user_id ?? 'N/A' }}</div>
        </div>

        <div class="bg-gray-50 p-3 rounded">
            <div class="text-xs text-gray-500">Archived By</div>
            <div class="font-medium">{{ $archive->archived_by ?? 'system' }}</div>
        </div>

        <div class="bg-gray-50 p-3 rounded">
            <div class="text-xs text-gray-500">Schema Version</div>
            <div class="font-medium">{{ $archive->schema_version ?? 'N/A' }}</div>
        </div>
        </div>

        <!-- details list -->
        @php
        $payload = $payload ?? [];
        $displayName = $payload['name'] ?? $payload['username'] ?? ($archive->username ?? 'N/A');
        $displayEmail = $archive->email ?? $payload['email'] ?? 'N/A';
        $displayUsername = $archive->username ?? $payload['username'] ?? ($payload['name'] ?? 'N/A');
        @endphp

        <div class="bg-white border rounded-lg p-4">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
            <h2 class="text-sm font-semibold break-words">{{ $displayName }}</h2>
            <p class="text-sm text-gray-600 break-words mt-1">{{ $displayEmail }}</p>
            </div>

            <div class="text-right text-sm text-gray-500">
            <div>Username</div>
            <div class="font-medium text-gray-800 mt-1">{{ $displayUsername }}</div>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 mt-4 text-sm text-gray-700">
            <div>
            <dt class="text-xs text-gray-500">Archive Reason</dt>
            <dd class="mt-1">{{ $archive->archive_reason ?? 'N/A' }}</dd>
            </div>

            <div>
            <dt class="text-xs text-gray-500">Archived At</dt>
            <dd class="mt-1">{{ \Carbon\Carbon::parse($archive->archived_at)->toDayDateTimeString() }}</dd>
            </div>
        </dl>
        </div>

        <!-- payload snapshot -->
        <div class="bg-gray-50 border rounded-lg p-4">
        <h3 class="text-sm font-semibold mb-2">Payload snapshot</h3>
        <pre class="text-xs text-gray-800 bg-white rounded p-3 overflow-auto" style="white-space: pre-wrap;">{{ json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>

        <!-- actions form -->
        <form id="restoreForm" action="{{ route('user_archive.restore', $archive->id) }}" method="POST" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        @csrf

        <div class="flex-1 space-y-3">
            <label class="flex items-center space-x-2">
            <input type="checkbox" id="send_password_reset" name="send_password_reset" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
            <span class="text-sm text-gray-700">Send password reset email after restore</span>
            </label>

            <div>
            <label for="conflict_strategy" class="text-sm text-gray-600 block mb-1">Collision strategy</label>
            <select name="conflict_strategy" id="conflict_strategy" class="w-full sm:w-80 border rounded px-3 py-2 text-sm">
                <option value="update">Update existing user if email exists</option>
                <option value="create_new">Create new user with modified email</option>
                <option value="manual">Flag for manual review</option>
            </select>
            </div>
        </div>

        <div class="flex-shrink-0 flex items-center gap-3">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700" onclick="return confirmRestore()">Restore user</button>
            <a href="{{ route('user_archive.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded hover:bg-gray-300">Back to archives</a>
        </div>
        </form>
    </div>
    </div>
</section>
</main>
@endsection

@section('scripts')
<script>
function confirmRestore() {
return confirm('Restore this archived user. This action will attempt to create or update a user. Continue?');
}
</script>
@endsection