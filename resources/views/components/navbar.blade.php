@php
    use Illuminate\Support\Facades\Auth;
@endphp

<aside class="bg-[#0b5a0b] w-48 h-screen flex flex-col items-center py-6 text-white select-none">
    <img src="{{ asset('PSAU_Logo.png') }}" 
         alt="Pamanga State Agricultural University official seal logo in green and yellow colors" 
         class="mb-3" width="100" height="100" />

    <h1 class="font-extrabold text-lg mb-6">
        Tamarind RDE
    </h1>

    <nav class="flex flex-col space-y-1 text-xs font-bold leading-tight">
        {{-- Dashboard redirect based on role --}}
        @auth
            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            @elseif(auth()->user()->hasRole('superadmin'))
                <a href="{{ route('superadmin.dashboard') }}">Dashboard</a>
            @elseif(auth()->user()->hasRole('user'))
                <a href="{{ route('user.dashboard') }}">Dashboard</a>
            @endif
        @endauth

        {{-- Visible to all roles: user, admin, superadmin --}}
        @hasanyrole('user|admin|superadmin')
            <a href="{{ route('trees.map') }}" class="hover:underline">Map</a>
            <a href="{{ route('pages.analytics') }}" class="hover:underline">Analytics</a>
            <a href="{{ route('pages.feedback') }}" class="hover:underline">Feedback</a>
        @endhasanyrole

        {{-- Visible to admin & superadmin --}}
        @hasanyrole('admin|superadmin')
            <a href="{{ route('pages.farm-data') }}" class="hover:underline">Farm Data</a>
            <a href="{{ route('pages.harvest-management') }}" class="hover:underline">Harvest Management</a>
            <a href="{{ route('pages.backup') }}" class="hover:underline">Backup</a>
            {{-- <a href="{{ route('trees.import') }}" class="hover:underline">Add tree</a> --}}
        @endhasanyrole

        {{-- Visible to superadmin only --}}
        @role('superadmin')
            <a href="{{ route('pages.accounts') }}" class="hover:underline">Accounts</a>
        @endrole

        {{-- Activity log for all authenticated users --}}
        @auth
            <a href="{{ route('pages.activity-log') }}" class="hover:underline">Activity Log</a>
        @endauth
    </nav>

    <div class="mt-auto text-[9px] px-2 text-white/80 select-text">
        © 2025 PSAU Tamarind RDE
    </div>
</aside>
