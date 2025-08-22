<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login - PSAU Tamarind RDE Center</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-start pt-2">

<img
src="{{ asset('PSAU_Logo.png') }}"
alt="Seal of Pampanga State Agricultural University"
class="w-40 h-40 mb-4"
/>

<div class="relative max-w-md w-full bg-white/30 backdrop-blur-md rounded-lg p-8 flex flex-col"
    style="background-color: rgba(255 255 255 / 0.3);">
<div class="absolute top-0 left-0 h-full w-4 rounded-l-lg" style="background-color: #0b5e07;"></div>

<h1 class="text-2xl font-extrabold text-green-800 mb-1 pl-2" style="font-family: Arial, sans-serif;">
    PSAU Tamarind RDE Center
</h1>
<p class="text-sm text-gray-900 mb-6 pl-2" style="font-family: Arial, sans-serif;">
    Access the system as Super Admin, Admin, or User.
</p>

{{-- Login Form --}}
<form method="POST" action="{{ route('login.check') }}" class="flex flex-col space-y-4">
    @csrf
        {{-- Error Message (shown under password) --}}
        @if (session()->has('error'))
            <div class="text-red-700 text-sm mt-2 px-3 py-2 bg-red-100 border border-red-300 rounded">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
            </div>
        @endif
    <div class="relative">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
            <i class="fas fa-user"></i>
        </span>
        <input
            type="email"
            name="email"
            value="{{ old('email') }}"
            placeholder="Email"
            class="pl-10 pr-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent w-full"
            required autofocus
        />
    </div>

    <div class="relative">
        <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
            <i class="fas fa-key"></i>
        </span>
        <input
            type="password"
            name="password"
            placeholder="Password"
            class="pl-10 pr-10 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent w-full"
            required
        />
    </div>

    <button
        type="submit"
        class="bg-green-800 text-white font-bold py-2 rounded-md hover:bg-green-900 transition-colors"
    >
        Login
    </button>
</form>
</div>

{{-- Background Image --}}
<img
src="{{ asset('tamarind-bg.png') }}"
alt="Tamarind products background"
class="fixed inset-0 -z-10 w-full h-full object-cover filter blur-sm brightness-75"
/>
</body>
</html>
