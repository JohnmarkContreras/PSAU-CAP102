{{-- resources/views/components/navbar.blade.php --}}

@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    $role = null;
    $unreadCount = 0;

    try {
        if (Auth::check()) {
            // Get role directly WITHOUT caching (cache loop is broken)
            $role = Auth::user()->getRoleNames()->first();
            
            // Get unread count directly WITHOUT caching
            $unreadCount = Auth::user()->unreadNotifications()->count();
        }
    } catch (\Throwable $e) {
        Log::error('Navbar data load failed: ' . $e->getMessage());
        $role = null;
        $unreadCount = 0;
    }
@endphp

<aside class="bg-[#003300] w-48 h-screen flex flex-col items-center py-6 text-white select-none">
    <img src="{{ asset('PSAU_Logo.png') }}" alt="Pamanga State Agricultural University official seal logo in green and yellow colors" class="mb-3" width="100" height="100" />
    <h1 class="font-extrabold text-sm mb-6">PSAU Tamarind R&DE</h1>

    <nav class="flex flex-col space-y-1 text-xs font-bold leading-tight">
        @auth
            {{-- DASHBOARD (Based on Role) --}}
            @if($role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('admin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            @elseif($role === 'superadmin')
                <a href="{{ route('superadmin.dashboard') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('superadmin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            @elseif($role === 'user')
                <a href="{{ route('user.dashboard') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('user.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            @endif

            {{-- Common Links for Admin & Superadmin --}}
            @if($role === 'admin' || $role === 'superadmin')
                <a href="{{ route('tree-images.index') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('tree-images.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Map
                </a>
                <a href="{{ route('analytics.carbon') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('analytics.carbon') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-chart-line text-lg text-gray-400"></i> Analytics
                </a>
                <a href="{{ route('accuracy.chart') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('accuracy.chart') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Accuracy
                </a>
            @endif

            {{-- User Links --}}
            @if($role === 'user')
                <a href="{{ route('tree-images.index') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('tree-images.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Map
                </a>
                <a href="{{ route('analytics.carbon') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('analytics.carbon') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-chart-line text-lg text-gray-400"></i> Analytics
                </a>
                <a href="{{ route('harvests.upcoming') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('harvests.upcoming') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-chart-pie text-lg text-gray-400"></i> Harvest Calendar
                </a>
            @endif

            {{-- Admin + Superadmin --}}
            @if($role === 'admin' || $role === 'superadmin')
                <a href="{{ route('pages.harvest-management') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.harvest-management') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-chart-pie text-lg text-gray-400"></i> Harvest Management
                </a>
                <a href="{{ route('pages.backup') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.backup') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-bars-progress text-lg text-gray-400"></i> Backup
                </a>
                <a href="{{ route('feedback.index') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('feedback.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-comment text-lg text-gray-400"></i> Manage Feedback
                </a>
                <a href="{{ route('pending-geotags.index') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pending-geotags.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-hourglass-half text-lg text-gray-400"></i> Pending Tags
                </a>
            @endif

            {{-- Superadmin --}}
            @if($role === 'superadmin')
                <a href="{{ route('pages.accounts') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.accounts') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-user text-lg text-gray-400"></i> Accounts
                </a>
                <a href="{{ route('pages.activity-log') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.activity-log') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-list-check text-md text-gray-400"></i> Activity Log
                </a>
            @endif

            {{-- Admin --}}
            @if($role === 'admin')
                <a href="{{ route('admin.user_table') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('admin.user_table') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-user text-lg text-gray-400"></i> User
                </a>
            @endif

            {{-- User Feedback --}}
            @if($role === 'user')
                <a href="{{ route('feedback.create') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('feedback.create') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-comment text-lg text-gray-400"></i> Feedback
                </a>
                <a href="{{ route('pages.backup') }}" class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.backup') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fa-solid fa-bars-progress text-lg text-gray-400"></i> Backup
                </a>
            @endif
        @endauth
    </nav>

    <div class="mt-auto text-[9px] px-2 text-white/80 select-text">© 2025 PSAU Tamarind RDE</div>
</aside>