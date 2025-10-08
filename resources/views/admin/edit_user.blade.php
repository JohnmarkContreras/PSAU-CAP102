@extends('layouts.app')

@section('title', 'Accounts')
{{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}

@section('content')
<main class="flex-1 p-6 space-y-6">
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Accounts">
                <div class="flex justify-end">
                    <button type="button" class="bg-gray-200 text-gray-800 px-4 py-1 mb-4 rounded cursor-pointer" onclick="history.back()">Back</button>
                </div>
            <div class="text-sm text-black/90 space-y-0.5">
                <div class="overflow-x-auto">

                    {{-- Success Message --}}
                    @if(session('success'))
                        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Users List Table --}}
                    <a href="{{ route('user_archive.index') }}" class="text-yellow-500 hover:underline">
                        <i class="fas fa-archive text-xl text-yellow-500"></i>
                        View archived accounts
                    </a>

                    <table class="hidden sm:table w-full bg-white border border-gray-200 rounded mt-2">
                        <thead>
                            <tr class="bg-gray-100 text-left text-sm font-semibold">
                                <th class="px-4 py-2 border border-gray-200">ID</th>
                                <th class="px-4 py-2 border border-gray-200">Name</th>
                                <th class="px-4 py-2 border border-gray-200">Email</th>
                                <th class="px-4 py-2 border border-gray-200">Role</th>
                                <th class="px-4 py-2 border border-gray-200">Status</th>
                                <th class="px-4 py-2 border border-gray-200">Actions</th>
                            </tr>
                        </thead>
                            <tbody class="text-sm">
                                @foreach ($users as $user)
                                    <tr class="hover:bg-gray-50">
                                        <form action="{{ route('admin.update_user', $user->id) }}" method="POST">
                                            @csrf
                                            <td class="px-4 py-2 border border-gray-200">{{ $user->id }}</td>
                                            <td class="px-4 py-2 border border-gray-200">{{ $user->name }}</td>
                                            <td class="px-4 py-2 border border-gray-200">{{ $user->email }}</td>

                                            {{-- Editable Role --}}
                                            <td class="px-4 py-2 border border-gray-200">
                                                <div class="relative">
                                                    <select name="role"
                                                        class="appearance-none border border-gray-300 rounded-lg px-3 py-2 w-full text-sm text-gray-700
                                                            focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                                {{ ucfirst($role->name) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-gray-400 pointer-events-none"></i>
                                                </div>
                                            </td>

                                            {{-- Editable Status --}}
                                            <td class="px-4 py-2 border border-gray-200">
                                                <div class="relative">
                                                    <select name="status"
                                                        class="appearance-none border border-gray-300 rounded-lg px-3 py-2 w-full text-sm text-gray-700
                                                            focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                                                        <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                    <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-gray-400 pointer-events-none"></i>
                                                </div>
                                            </td>

                                            {{-- Actions --}}
                                            <td class="px-4 py-2 border border-gray-200 text-center">
                                                <button type="submit"
                                                    class="bg-green-600 text-white text-sm font-medium
                                                        px-4 py-1.5 rounded-md transition duration-150 ease-in-out
                                                        hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1">
                                                    <i class="fa-solid fa-floppy-disk text-sm mr-1"></i>
                                                    Save
                                                </button>
                                            </td>
                                        </form>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    {{-- Mobile View --}}
                    <div class="sm:hidden space-y-4 mt-4">
                        @foreach ($users as $user)
                            <form action="{{ route('admin.update_user', $user->id) }}" method="POST" class="border rounded p-3 bg-white shadow-sm">
                                @csrf
                                <p><span class="font-semibold">ID:</span> {{ $user->id }}</p>
                                <p><span class="font-semibold">Name:</span> {{ $user->name }}</p>
                                <p><span class="font-semibold">Email:</span> {{ $user->email }}</p>

                                <div class="mt-2">
                                    <label class="font-semibold">Role:</label>
                                    <select name="role" class="border rounded p-1 w-full">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mt-2">
                                    <label class="font-semibold">Status:</label>
                                    <select name="status" class="border rounded p-1 w-full">
                                        <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>

                                <div class="text-right mt-3">
                                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">
                                        Save
                                    </button>
                                </div>
                            </form>
                        @endforeach
                    </div>

                </div>
            </div>
        </x-card>
    </section>
</main>
@endsection
