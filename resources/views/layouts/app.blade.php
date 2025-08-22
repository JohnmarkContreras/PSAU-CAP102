<!DOCTYPE html>
<html lang="en">
<head>
    @php
    $role = Auth::user()->role;
    @endphp
    @livewireStyles
    <meta charset="UTF-8">
    <title>@yield('title', 'PSAU Tamarind RDE')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" />
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 flex">
        
    {{-- DELETE MESSAGE --}}
    {{-- @if(session('success'))
            <div class="bg-green-200 text-green-800 px-4 py-2 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-200 text-red-800 px-4 py-2 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif --}}

    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 bg-[#0b5a0b] w-38 h-screen flex flex-col items-center py-6 text-white select-none z-50">
        @include('components.navbar')
    </aside>

    <!-- Page Wrapper -->
    <div class="ml-60 flex flex-col min-h-screen w-full">
        <!-- Page Content -->
        <main class="flex-grow p-6">
            @yield('content')
        </main>
    </div>

    <aside>
        
    <div class="relative p-4 flex">
        <div class="flex p-4 items-center space-x-2 whitespace-nowrap">
        <span class="text-l font-medium">{{ Auth::user()->name }}-{{ Auth::user()->getRoleNames()->first() }}
</span>
    </div>

        <button id="dropdownBtn" class="text-3xl">
            <i class="fa-solid fa-user"></i>
        </button>
    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-32 h-16 bg-white shadow rounded">
        <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Profile</a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>

        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 text-sm hover:bg-gray-100">
            Logout
        </a>
    </div>

    </div>
    </div>

    <script>
    document.addEventListener('click', e => {
        const btn = document.getElementById('dropdownBtn');
        const menu = document.getElementById('dropdownMenu');
        if (btn.contains(e.target)) menu.classList.toggle('hidden');
        else if (!menu.contains(e.target)) menu.classList.add('hidden');
    });
    </script>

    </aside>
    @livewireStyles
</body>
</html>
