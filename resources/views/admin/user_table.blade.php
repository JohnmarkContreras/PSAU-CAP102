@extends('layouts.app')

@section('title', 'Accounts')
@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Accounts">
            <div class="flex justify-end">
                <button type="button" class="bg-gray-200 text-gray-800 px-4 py-1 mb-2 rounded cursor-pointer" onclick="history.back()">Back</button>
            </div>

            <div class="text-sm text-black/90 space-y-0.5">
                <div class="overflow-x-auto">
                    {{-- Archive Link --}}
                    <a href="{{ route('user_archive.index') }}" class="text-yellow-500 hover:underline">
                        <i class="fas fa-archive text-xl text-yellow-500"></i>
                        View archived accounts
                    </a>

                    {{-- Users Table --}}
                    <table id="accountsTable" class="hidden sm:table w-full bg-white border border-gray-200 rounded-lg">
                        <thead>
                            <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
                                <th class="px-4 py-2 border border-gray-200">ID</th>
                                <th class="px-4 py-2 border border-gray-200">Photo</th>
                                <th class="px-4 py-2 border border-gray-200">Name</th>
                                <th class="px-4 py-2 border border-gray-200">Email</th>
                                <th class="px-4 py-2 border border-gray-200">Role</th>
                                <th class="px-4 py-2 border border-gray-200 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-gray-700">
                            @foreach ($users as $user)
                                <tr class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-4 py-2 border border-gray-200">{{ $user->id }}</td>

                                    {{-- Profile Picture --}}
                                    <td class="px-4 py-2 border border-gray-200">
                                        <div class="flex items-center justify-center">
                                            @if($user->profile_picture)
                                                <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                    alt="Profile Picture"
                                                    class="w-10 h-10 rounded-full object-cover border border-gray-300">
                                            @else
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff"
                                                    alt="Default Avatar"
                                                    class="w-10 h-10 rounded-full object-cover border border-gray-300">
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-2 border border-gray-200">{{ $user->name }}</td>
                                    <td class="px-4 py-2 border border-gray-200">{{ $user->email }}</td>
                                    <td class="px-4 py-2 border border-gray-200 capitalize">
                                        {{ $user->getRoleNames()->implode(', ') }}
                                    </td>

                                    <td class="px-4 py-2 border border-gray-200">
                                        <div class="flex justify-center items-center gap-4">
                                            {{-- Delete --}}
                                            <form action="{{ route('superadmin.delete.account', $user->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure?');"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:text-red-700 transition duration-150 ease-in-out">
                                                    <i class="fas fa-trash text-xl"></i>
                                                </button>
                                            </form>

                                            {{-- Archive --}}
                                            <form action="{{ route('users.archive', $user->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Archive this user?');"
                                                class="inline">
                                                @csrf
                                                <input name="reason" value="archived by admin" hidden>
                                                <button type="submit" class="text-yellow-500 hover:text-yellow-600 transition duration-150 ease-in-out">
                                                    <i class="fas fa-archive text-xl"></i>
                                                </button>
                                            </form>

                                            {{-- Edit --}}
                                            <a href="{{ route('admin.edit_user', $user->id) }}"
                                            class="text-green-600 hover:text-green-700 transition duration-150 ease-in-out">
                                                <i class="fa-solid fa-pen-to-square text-xl"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Mobile View --}}
                    <div class="sm:hidden space-y-4">
                        @foreach ($users as $user)
                            <div class="border rounded p-3 bg-white">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        @if($user->profile_picture)
                                            <img src="{{ asset('storage/' . $user->profile_picture) }}"
                                                alt="Profile Picture"
                                                class="w-12 h-12 rounded-full object-cover border border-gray-300">
                                        @else
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff"
                                                alt="Default Avatar"
                                                class="w-12 h-12 rounded-full object-cover border border-gray-300">
                                        @endif
                                        <div>
                                            <p class="font-semibold">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        {{-- Delete --}}
                                        <form action="{{ route('superadmin.delete.account', $user->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline">
                                                <i class="fas fa-trash text-xl text-red-700 cursor-pointer"></i>
                                            </button>
                                        </form>

                                        {{-- Archive --}}
                                        <form action="{{ route('users.archive', $user->id) }}" method="POST" onsubmit="return confirm('Archive this user?');">
                                            @csrf
                                            <input name="reason" value="archived by admin" hidden>
                                            <button type="submit">
                                                <i class="fas fa-archive text-xl text-yellow-600 cursor-pointer"></i>
                                            </button>
                                        </form>

                                        {{-- Edit --}}
                                        <a href="{{ route('admin.edit_user', $user->id) }}">
                                            <i class="fa-solid fa-pen-to-square text-xl text-green-700 cursor-pointer"></i>
                                        </a>
                                    </div>
                                </div>

                                <p><span class="font-semibold">Role:</span> {{ $user->getRoleNames()->implode(', ') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-card>
    </section>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#accountsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']],
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search accounts...",
                lengthMenu: "Show _MENU_ entries",
                paginate: {
                    previous: "← Prev",
                    next: "Next →"
                },
                info: "Showing _START_ to _END_ of _TOTAL_ accounts",
                infoEmpty: "No accounts available",
            },
            columnDefs: [
                { orderable: false, targets: [1, 5] } // Disable sorting for photo & actions
            ]
        });
    });
</script>
@endpush