@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Accounts">
                    <div class="text-sm text-black/90 space-y-0.5">

                        {{-- Create New User Button --}}
                        <div class="mb-4">
                            <a href="{{ route('create.account') }}" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                                + Create New Account
                            </a>
                        </div>

                        {{-- Users List Table --}}
                        <table class="min-w-full bg-white border rounded">
                            <thead>
                                <tr class="bg-gray-100 text-left text-sm font-semibold">
                                    <th class="px-4 py-2 border">ID</th>
                                    <th class="px-4 py-2 border">Name</th>
                                    <th class="px-4 py-2 border">Email</th>
                                    <th class="px-4 py-2 border">Role</th>
                                    <th class="px-4 py-2 border">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @foreach ($users as $user)
                                    <tr class="border-t hover:bg-gray-50">
                                        <td class="px-4 py-2 border">{{ $user->id }}</td>
                                        <td class="px-4 py-2 border">{{ $user->name }}</td>
                                        <td class="px-4 py-2 border">{{ $user->email }}</td>
                                        <td class="px-4 py-2 border capitalize">{{ $user->getRoleNames()->implode(', ')}}</td>
                                        <td class="px-4 py-2 border">
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
                        </table>
                    </x-card>
            </section>
        </main>
@endsection
