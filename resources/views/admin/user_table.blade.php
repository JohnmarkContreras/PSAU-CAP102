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
                        {{-- Users List Table --}}
                            <a href="{{ route('user_archive.index') }}" class="text-yellow-500 hover:underline">
                                <i class="fas fa-archive text-xl text-yellow-500"></i>
                                View archived accounts
                            </a>
                        <table class="hidden sm:table w-full bg-white border border-gray-200 rounded-lg mt-2">
                            <thead>
                                <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
                                    <th class="px-4 py-2 border border-gray-200">ID</th>
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
                                        <td class="px-4 py-2 border border-gray-200">{{ $user->name }}</td>
                                        <td class="px-4 py-2 border border-gray-200">{{ $user->email }}</td>
                                        <td class="px-4 py-2 border border-gray-200 capitalize">
                                            {{ $user->getRoleNames()->implode(', ') }}
                                        </td>

                                        <td class="px-4 py-2 border border-gray-200">
                                            <div class="flex justify-center items-center gap-4">

                                                {{-- Delete Button --}}
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

                            {{-- mobile view --}}
                            <div class="sm:hidden space-y-4">
                                @foreach ($users as $user)
                                    <div class="border rounded p-3 bg-white">
                                        <div class="mt-2 flex items-center gap-2 float-right">
                                            {{-- Delete Button --}}
                                            <form action="{{ route('superadmin.delete.account', $user->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline"><i class="fas fa-trash text-2xl text-red-700 cursor-pointer"></i></button>
                                            </form>
                                            {{-- Optional: Edit or view --}}
                                            <form action="{{ route('users.archive', $user->id) }}" method="POST" onsubmit="return confirm('Archive this user?');">
                                                @csrf
                                                <input name="reason" value="archived by admin" hidden>
                                                <button type="submit" class="btn btn-warning"><i class="fas fa-archive text-2xl text-yellow-600 cursor-pointer"></i></button>
                                            </form>
                                            {{-- Edit profile --}}
                                            <form action="{{ route('profile.index') }}" method="GET" onsubmit="return confirm('Edit this user?');">
                                                @csrf
                                                <input name="edit" hidden>
                                                <button type="submit" class="btn btn-warning"><i class="fa-solid fa-pen-to-square text-2xl text-green-700 cursor-pointer"></i></button>
                                            </form>
                                        </div>
                                        <p><span class="font-semibold">ID:</span> {{ $user->id }}</p>
                                        <p><span class="font-semibold">Name:</span> {{ $user->name }}</p>
                                        <p><span class="font-semibold">Email:</span> {{ $user->email }}</p>
                                        <p><span class="font-semibold">Role:</span> {{ $user->getRoleNames()->implode(', ') }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </table>
                        </div>
                    </x-card>
            </section>
        </main>
@endsection
