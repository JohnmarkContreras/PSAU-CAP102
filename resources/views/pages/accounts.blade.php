@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Accounts">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <div class="overflow-x-auto">
                        {{-- Create New User Button --}}
                        <div class="mb-4">
                            <a href="{{ route('create.account') }}" class="bg-green-800 hover:bg-green-700 text-white py-2 px-4 rounded">
                                + Create New Account
                            </a>
                        </div>

                        {{-- Users List Table --}}
                        <table class="hidden sm:table w-full bg-white rounded">
                            <thead>
                                <tr class="bg-gray-100 text-left text-sm font-semibold">
                                    <th class="px-4 py-2">ID</th>
                                    <th class="px-4 py-2">Name</th>
                                    <th class="px-4 py-2">Email</th>
                                    <th class="px-4 py-2">Role</th>
                                    <th class="px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @foreach ($users as $user)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2">{{ $user->id }}</td>
                                        <td class="px-4 py-2">{{ $user->name }}</td>
                                        <td class="px-4 py-2">{{ $user->email }}</td>
                                        <td class="px-4 py-2 capitalize">{{ $user->getRoleNames()->implode(', ')}}</td>
                                        <td class="px-4 py-2">
                                            {{-- Delete Button --}}
                                            <form action="{{ route('superadmin.delete.account', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                            {{-- Optional: Edit or view --}}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            {{-- mobile view --}}
                            <div class="sm:hidden space-y-4">
                                @foreach ($users as $user)
                                    <div class="border rounded p-3 bg-white">
                                        <p><span class="font-semibold">ID:</span> {{ $user->id }}</p>
                                        <p><span class="font-semibold">Name:</span> {{ $user->name }}</p>
                                        <p><span class="font-semibold">Email:</span> {{ $user->email }}</p>
                                        <p><span class="font-semibold">Role:</span> {{ $user->getRoleNames()->implode(', ') }}</p>
                                        <div class="mt-2">
                                            <form action="{{ route('superadmin.delete.account', $user->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </table>
                        </div>
                    </x-card>
            </section>
        </main>
@endsection
