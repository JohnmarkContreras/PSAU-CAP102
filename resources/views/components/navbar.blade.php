@php
    use Illuminate\Support\Facades\Auth;
@endphp

<aside class="bg-[#0b5a0b] w-48 h-screen flex flex-col items-center py-6 text-white select-none">
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
                    class="px-3 py-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                    Dashboard
                </a>
            @elseif(auth()->user()->hasRole('superadmin'))
                <a href="{{ route('superadmin.dashboard') }}" 
                    class="px-3 py-2 rounded {{ request()->routeIs('superadmin.dashboard') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                    Dashboard
                </a>
            @elseif(auth()->user()->hasRole('user'))
                <a href="{{ route('user.dashboard') }}" 
                    class="px-3 py-2 rounded {{ request()->routeIs('user.dashboard') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                    Dashboard
                </a>
            @endif
        @endauth

        {{-- Visible to all roles --}}
        @hasanyrole('user|admin|superadmin')
            <a href="{{ route('trees.map') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('trees.map') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Map
            </a>
            <a href="{{ route('pages.analytics') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('pages.analytics') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Analytics
            </a>
        @endhasanyrole

        {{-- Admin + superadmin --}}
        @hasanyrole('admin|superadmin')
            <a href="{{ route('pages.harvest-management') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('pages.harvest-management') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Harvest Management
            </a>
            <a href="{{ route('pages.backup') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('pages.backup') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Backup
            </a>
            <a href="{{ route('feedback.index') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('feedback.index') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Manage Feedback
            </a>
        @endhasanyrole

        {{-- Superadmin --}}
        @role('superadmin')
            <a href="{{ route('pages.accounts') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('pages.accounts') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Accounts
            </a>
        @endrole

        {{-- Activity Log --}}
        @auth
            <a href="{{ route('pages.activity-log') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('pages.activity-log') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Activity Log
            </a>
        @endauth

        {{-- User Feedback --}}
        @role('user')
            <a href="{{ route('feedback.create') }}" 
                class="px-3 py-2 rounded {{ request()->routeIs('feedback.create') ? 'bg-white text-[#0b5a0b]' : 'hover:underline' }}">
                Feedback
            </a>
        @endrole
    </nav>


    <div class="mt-auto text-[9px] px-2 text-white/80 select-text">
        © 2025 PSAU Tamarind RDE
    </div>
</aside>
