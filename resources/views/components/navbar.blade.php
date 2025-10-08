@php
    use Illuminate\Support\Facades\Auth;
@endphp

<aside class="bg-[#003300] w-48 h-screen flex flex-col items-center py-6 text-white select-none">
    <img src="{{ asset('PSAU_Logo.png') }}" 
        alt="Pamanga State Agricultural University official seal logo in green and yellow colors" 
        class="mb-3" width="100" height="100" />

    <h1 class="font-extrabold text-sm mb-6">
        PSAU Tamarind R&DE
    </h1>

    <nav class="flex flex-col space-y-1 text-xs font-bold leading-tight">
        {{-- Dashboard redirect based on role --}}
        @auth
            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}" 
                    class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('admin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i>
                    Dashboard
                </a>
            @elseif(auth()->user()->hasRole('superadmin'))
                <a href="{{ route('superadmin.dashboard') }}" 
                    class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('superadmin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i>
                    Dashboard
                </a>
            @elseif(auth()->user()->hasRole('user'))
                <a href="{{ route('user.dashboard') }}" 
                    class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('user.dashboard') ? 'bg-white text-green-800' : 'hover:underline' }}">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i>
                    Dashboard
                </a>
            @endif
        @endauth

        {{-- Visible to all roles --}}
        @hasanyrole('user|admin|superadmin')
            <a href="{{ route('tree-images.index') }}" 
                class="px-3 py-2 rounded flex items-center gap-3  {{ request()->routeIs('tree-images.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline' }}">
                <i class="fas fa-map-pin text-xl text-gray-400"></i>
                Map
            </a>
            <a href="{{ route('analytics.carbon') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('analytics.carbon') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-chart-line text-lg text-gray-400"></i>
                Analytics
            </a>
            @php
                $unreadCount = auth()->user()->unreadNotifications()->count();
            @endphp

            <a href="{{ route('pages.notifications') }}" 
            class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.notifications') ? 'bg-white text-green-800' : 'hover:underline' }}">
                
                <div class="relative">
                    <i class="fa-solid fa-bell text-lg text-gray-400"></i>
                </div>
                <span>Notifications</span>
                @if($unreadCount > 0)
                    <span class="relative top-0 right-0 bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                        {{ $unreadCount }}
                    </span>
                @endif
            </a>



        @endhasanyrole

        {{-- Admin + superadmin --}}
        @hasanyrole('admin|superadmin')
            <a href="{{ route('pages.harvest-management') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.harvest-management') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-chart-pie text-lg text-gray-400"></i>
                Harvest Management
            </a>
            <a href="{{ route('pages.backup') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.backup') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-bars-progress text-lg text-gray-400"></i>
                Backup
            </a>
            <a href="{{ route('feedback.index') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('feedback.index') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-comment text-lg text-gray-400"></i>
                Manage Feedback
            </a>
            <a href="{{ route('pages.activity-log') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.activity-log') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-list-check text-md text-gray-400"></i>
                Activity Log
            </a>
            <a href="{{ route('pending-geotags.index') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pending-geotags.index') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-hourglass-half text-lg text-gray-400"></i>
                Pending tags
            </a>
        @endhasanyrole

        {{-- Superadmin --}}
        @role('superadmin')
            <a href="{{ route('pages.accounts') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('pages.accounts') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-user text-lg text-gray-400"></i>
                Accounts
            </a>
        @endrole

        {{-- User Feedback --}}
        @role('admin')
            <a href="{{ route('admin.user_table') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('admin.user_table') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-user text-lg text-gray-400"></i>
                User
            </a>
        @endrole

        {{-- User Feedback --}}
        @role('user')
            <a href="{{ route('feedback.create') }}" 
                class="px-3 py-2 rounded flex items-center gap-3 {{ request()->routeIs('feedback.create') ? 'bg-white text-green-800' : 'hover:underline' }}">
                <i class="fa-solid fa-comment text-lg text-gray-400"></i>
                Feedback
            </a>
        @endrole

    </nav>


    <div class="mt-auto text-[9px] px-2 text-white/80 select-text">
        © 2025 PSAU Tamarind RDE
    </div>
</aside>
