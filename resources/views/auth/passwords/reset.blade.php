<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Password - PSAU Tamarind RDE Center</title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/app.js') }}" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>

<body class="min-h-screen bg-gray-100 relative flex items-center justify-center">

    {{-- Background --}}
    <img
        src="{{ asset('tamarind-bg.png') }}"
        alt="Tamarind products background"
        class="absolute inset-0 -z-10 w-full h-full object-cover filter blur-sm brightness-75"
    />

    {{-- Reset Container --}}
    <div class="flex flex-col items-center justify-center w-full px-4 sm:px-0">
        {{-- Logo --}}
        <img
            src="{{ asset('PSAU_Logo.png') }}"
            alt="Seal of Pampanga State Agricultural University"
            class="w-28 h-28 mb-4 drop-shadow-md"
        />

        {{-- Reset Card --}}
        <div class="relative max-w-md w-full bg-white/40 backdrop-blur-md rounded-lg p-8 shadow-lg border border-green-900/20">
            <div class="absolute top-0 left-0 h-full w-4 rounded-l-lg bg-green-800"></div>

            <h1 class="text-2xl font-extrabold text-green-800 mb-2 text-center">
                Reset Password
            </h1>

            <form method="POST" action="{{ route('password.update') }}" class="flex flex-col space-y-4 mt-6">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                {{-- Email --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ $email ?? old('email') }}"
                        required
                        autofocus
                        placeholder="Email Address"
                        class="pl-10 pr-3 py-2 rounded-md border border-gray-300 w-full focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                    />
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        placeholder="New Password"
                        class="pl-10 pr-3 py-2 rounded-md border border-gray-300 w-full focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                    />
                    @error('password')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <input
                        id="password-confirm"
                        type="password"
                        name="password_confirmation"
                        required
                        placeholder="Confirm Password"
                        class="pl-10 pr-3 py-2 rounded-md border border-gray-300 w-full focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent"
                    />
                </div>

                {{-- Submit Button --}}
                <button
                    type="submit"
                    class="w-full bg-green-800 text-white font-bold py-3 rounded-md hover:bg-green-900 transition-colors"
                >
                    Reset Password
                </button>

                <div class="text-center">
                    <a href="{{ route('login') }}" class="text-sm text-black hover:underline">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
