<!DOCTYPE html>
<html lang="en">
<head>
    @php $role = Auth::user()->role; @endphp
    @livewireStyles
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'PSAU Tamarind RDE')</title>
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/app.js') }}" defer></script>
</head>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" />
    
<body class="bg-gray-100 text-gray-900 min-h-screen flex">

    <!-- Sidebar (Desktop) -->
    <aside class="hidden md:flex fixed top-0 left-0 bg-[#0b5a0b] w-60 h-screen flex-col items-center py-6 text-white z-50">
        @include('components.navbar')
    </aside>

    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed top-0 left-0 right-0 bg-[#0b5a0b] text-white flex justify-between items-center px-4 py-3 z-40">
        <span class="font-bold text-lg">PSAU Tamarind R&DE</span>
        <button id="mobileMenuBtn" class="text-2xl" aria-controls="mobileSidebar" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- Mobile Sidebar (Slide-over) -->
    <div id="mobileSidebar"
         class="fixed inset-0 z-50 hidden"
         aria-hidden="true">
        <!-- Backdrop -->
        <div id="backdrop"
             class="absolute inset-0 bg-black transition-opacity duration-300 opacity-0"></div>

        <!-- Panel -->
        <aside id="sidebarPanel"
               class="relative bg-[#0b5a0b] w-60 h-full p-6 text-white transform -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
            <button id="closeSidebar" class="text-white text-xl mb-6" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
            @include('components.navbar')
        </aside>
    </div>

    <!-- Page Wrapper -->
    <div class="flex flex-col flex-1 w-full md:ml-60">
        <!-- Spacer for fixed mobile header -->
        <div class="md:hidden h-[56px]"></div>

        <!-- Top Right User Info -->
        <div class="p-4 flex justify-end items-center gap-3">
            <span class="text-sm md:text-base font-medium whitespace-nowrap">
                {{ Auth::user()->name }} - {{ Auth::user()->getRoleNames()->first() }}
            </span>
            <div class="relative">
                <button id="dropdownBtn" class="text-2xl" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-user"></i>
                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-36 bg-white shadow rounded overflow-hidden z-50">
                    <a href="{{ route('profile.index') }}" class="block px-4 py-2 text-sm hover:bg-gray-100">Profile</a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>

        <!-- Page Content -->
        <main class="flex-grow p-6 z-0">
            @yield('content')
        </main>
    </div>

    <script>
        // User dropdown
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('dropdownBtn');
            const menu = document.getElementById('dropdownMenu');
            if (!btn || !menu) return;

            if (btn.contains(e.target)) {
                menu.classList.toggle('hidden');
            } else if (!menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        // Mobile slide-over
        const mobileSidebar = document.getElementById('mobileSidebar');
        const sidebarPanel = document.getElementById('sidebarPanel');
        const backdrop = document.getElementById('backdrop');
        const openBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('closeSidebar');

        function openSidebar() {
            mobileSidebar.classList.remove('hidden');
            // allow reflow so transitions apply
            requestAnimationFrame(() => {
                sidebarPanel.classList.remove('-translate-x-full');
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                openBtn?.setAttribute('aria-expanded', 'true');
                mobileSidebar.setAttribute('aria-hidden', 'false');
            });
        }

        function closeSidebar() {
            sidebarPanel.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => {
                mobileSidebar.classList.add('hidden');
                openBtn?.setAttribute('aria-expanded', 'false');
                mobileSidebar.setAttribute('aria-hidden', 'true');
            }, 300); // match duration-300
        }

        openBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        backdrop?.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

        // If user resizes to md+ while open, reset
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                mobileSidebar.classList.add('hidden');
                sidebarPanel.classList.add('-translate-x-full');
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
            }
        });
    </script>

    @livewireScripts
</body>
</html>
