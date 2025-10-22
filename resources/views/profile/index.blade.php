@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="min-h-screen flex justify-center items-start bg-gray-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-2xl bg-white p-6 rounded-2xl shadow-md">
        <h2 class="text-xl font-bold mb-4 text-center">My Profile</h2>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-center">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="w-full">
            @csrf
            @method('PUT')

            {{-- Profile Picture --}}
            <div class="mb-6 text-center">
                <div class="flex justify-center mb-3">
                    @if($user->profile_picture)
                        <img id="preview-image"
                            src="{{ asset('storage/' . $user->profile_picture) }}"
                            alt="Profile Picture"
                            class="w-32 h-32 rounded-full object-cover border-2 border-gray-300 shadow-sm">
                    @else
                        <img id="preview-image"
                            src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0D8ABC&color=fff"
                            alt="Profile Picture"
                            class="w-32 h-32 rounded-full object-cover border-2 border-gray-300 shadow-sm">
                    @endif
                </div>

                <input type="file" name="profile_picture" id="profile_picture"
                    accept="image/*"
                    class="block w-full text-sm text-gray-600 border border-gray-300 rounded cursor-pointer bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    onchange="previewImage(event)">
                <p class="text-xs text-gray-500 mt-1">Upload a JPG or PNG image (max 2MB).</p>
            </div>

            {{-- Name --}}
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="name"
                    value="{{ old('name', $user->name) }}"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required>
            </div>

            {{-- Email --}}
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email"
                    value="{{ old('email', $user->email) }}"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required>
            </div>

            {{-- Phone Number --}}
            <div class="mb-4">
                <label for="number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="number" id="number"
                    value="{{ old('number', $user->number) }}"
                    placeholder="e.g. 09766027331"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    required>
                <p class="text-xs text-gray-500 mt-1">Use your active mobile number for SMS updates.</p>
            </div>

            {{-- New Password --}}
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">
                    New Password
                    <span class="text-xs text-gray-500">(leave blank if not changing)</span>
                </label>
                <input type="password" name="password" id="password"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            {{-- Confirm Password --}}
            <div class="mb-6">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            {{-- Submit Button --}}
            <div class="text-center">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Image Preview Script --}}
<script>
    function previewImage(event) {
        const input = event.target;
        const reader = new FileReader();
        reader.onload = function() {
            const img = document.getElementById('preview-image');
            img.src = reader.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
</script>

{{-- Preserve scroll and prevent layout shift --}}
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const scrollPos = sessionStorage.getItem("scrollPos");
        if (scrollPos) {
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem("scrollPos");
        }

        // Store scroll position before leaving page
        window.addEventListener("beforeunload", function() {
            sessionStorage.setItem("scrollPos", window.scrollY);
        });
    });
</script>
@endsection
