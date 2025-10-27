@if(session('success') || session('status'))
    <div id="toast"
        class="fixed inset-0 flex items-center justify-center z-50">
        <div class="px-6 py-3 rounded shadow-lg text-white text-center
                    {{ session('success') ? 'bg-green-600' : 'bg-red-600' }}">
            {{ session('success') ?? session('status') }}
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('opacity-0', 'transition', 'duration-700');
                    setTimeout(() => toast.remove(), 700);
                }, 3000); // auto-hide after 3s
            }
        });
    </script>
@endif

    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@extends('layouts.app')

@section('content')
    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
        <x-card title="Pending tags">
            <div class="text-sm text-black/90 space-y-0.5">
                @if($pending->isEmpty())
                <div class="mb-4 text-right">
                    <a href="{{ route('geotags.history') }}" class="text-blue-600 hover:underline font-medium">
                        View Approved & Rejected Geotags
                    </a>
                </div>
                    <p>No pending geotags.</p>
                @else
                <div class="mb-4 text-right">
                    <a href="{{ route('geotags.history') }}" class="text-blue-600 hover:underline font-medium">
                        View Approved & Rejected Geotags
                    </a>
                </div>

                    <div class="overflow-x-auto min-h-[600px]">
                        <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg mt-2">
                            <thead class="bg-gray-100">
                                <tr class="text-center">
                                    <th class="p-2">User</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Latitude</th>
                                    <th>Longitude</th>
                                    <th>Age</th>
                                    <th>Height</th>
                                    <th>Stem Diameter</th>
                                    <th>Canopy Diameter</th>
                                    <th colspan="2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pending as $geo)
                                <tr class="hover:bg-gray-50 text-center">
                                    <td class="px-4 py-2">{{ $geo->user->name }}</td>
                                    <td class="px-4 py-2">{{ $geo->code }}</td>
                                    <td class="px-4 py-2">{{ $geo->type }}</td>
                                    <td class="px-4 py-2">{{ $geo->latitude }}</td>
                                    <td class="px-4 py-2">{{ $geo->longitude }}</td>
                                    <td class="px-4 py-2">{{ $geo->age }}</td>
                                    <td class="px-4 py-2">{{ $geo->height }}</td>
                                    <td class="px-4 py-2">{{ $geo->stem_diameter }}</td>
                                    <td class="px-4 py-2">{{ $geo->canopy_diameter }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @if($geo->status === 'pending')
                                            <form x-data="{ loading: false, done: false }"
                                                @submit.prevent="
                                                    loading = true;
                                                    fetch($el.action, {
                                                        method: 'POST',
                                                        body: new FormData($el),
                                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
                                                    })
                                                    .then(res => res.ok ? res.text() : Promise.reject())
                                                    .then(() => {
                                                        done = true;

                                                        // Find the row and remove it with a quick fade
                                                        const row = $el.closest('tr');
                                                        if (row) {
                                                            row.style.height = `${row.offsetHeight}px`;     // lock current height
                                                            row.style.transition = 'opacity 200ms ease, height 200ms ease';
                                                            row.style.opacity = '0';
                                                            requestAnimationFrame(() => { row.style.height = '0px'; });
                                                            setTimeout(() => row.remove(), 220);
                                                        }
                                                    })
                                                    .catch(() => {
                                                        // Optional: show a centered toast or revert button state
                                                        done = false;
                                                    })
                                                    .finally(() => loading = false);
                                                "
                                                action="{{ route('pending-geotags.approve', $geo->id) }}"
                                                method="POST"
                                                class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="p-2 w-20 rounded text-white bg-green-800 hover:bg-green-700"
                                                    :class="{ 'opacity-50 cursor-not-allowed': loading || done }">
                                                    <span x-show="!loading && !done">Approve</span>
                                                    <span x-show="loading" class="animate-spin">⏳</span>
                                                    <span x-show="done">✔</span>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 italic">—</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-2 text-center">
                                        @if($geo->status === 'pending')
                                            <form action="{{ route('pending-geotags.reject', $geo->id) }}" method="POST" class="inline">
                                                @csrf
                                                @include('partials.reject', ['geotag' => $geo])
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination mt-4">
                        {{ $pending->links('pagination::tailwind') }}
                    </div>
                @endif
            </div>
        </x-card>
        <!-- Loading Overlay -->
        <div id="loading-overlay" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
            <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-white border-solid"></div>
        </div>
    </section>

    <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Pending dead tree">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <a href="{{ route('dead-tree-requests.index') }}" class="text-blue-600 hover:underline font-medium">
                            View pending dead tree for approval
                    </a>
                    </div>
                </x-card>
        </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('loading-overlay');

        // Attach to all pagination links
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function () {
                overlay.classList.remove('hidden');
            });
        });
    });
</script>

@endsection
