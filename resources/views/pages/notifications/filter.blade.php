@extends('layouts.app') <!-- Inherit the layout -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script> --}}
@section('title', 'Notifications')

@section('content')
    <main class="flex-1 p-6 space-y-6">
        <section class="bg-[#e9eee9] rounded-lg p-4 relative">
                <x-card title="Notifications">
                    <div class="text-sm text-black/90 space-y-0.5">
                        <div class="mb-4">
                            <!-- Tree filter buttons -->
                            <div class="flex space-x-6 mb-4 border-b border-gray-300">
                                <button type="button" data-filter="all"
                                    class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 transition duration-150 ease-in-out cursor-pointer
                                        {{ $filter === 'all' ? 'border-green-600 text-green-800' : 'border-transparent text-gray-600 hover:text-blue-600 hover:border-blue-400' }}">
                                    All
                                </button>

                                <button type="button" data-filter="new"
                                    class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 transition duration-150 ease-in-out cursor-pointer
                                        {{ $filter === 'new' ? 'border-green-600 text-green-800' : 'border-transparent text-gray-600 hover:text-blue-600 hover:border-blue-400' }}">
                                    New
                                </button>

                                <button type="button" data-filter="unread"
                                    class="tab-btn p-2 text-sm font-medium pb-2 border-b-2 transition duration-150 ease-in-out cursor-pointer
                                        {{ $filter === 'unread' ? 'border-green-600 text-green-800' : 'border-transparent text-gray-600 hover:text-blue-600 hover:border-blue-400' }}">
                                    Unread
                                </button>
                            </div>
                            <!-- Mark all button under filters -->
                            <form method="POST" action="{{ route('notifications.markAllRead') }}">
                                @csrf
                                <button type="submit"
                                    class="text-sm bg-[#e9eee9] rounded-sm w-32 h-10 text-green-800 hover:underline font-medium cursor-pointer">
                                    Mark all as read
                                </button>
                            </form>
                        </div>

                        </div>
                            {{-- Tabs --}}
                            <div id="notification-list">
                            {{-- Notifications --}}
                                @include('partials.notifications', ['notifications' => $notifications])
                            </div>
                            <div id="loading-spinner" class="hidden text-center py-4">
                                <span class="text-gray-500 text-sm animate-pulse">Loading notifications...</span>
                            </div>
                    </div>
                </x-card>
        </section>
    </main>
@endsection
<script>
document.addEventListener('DOMContentLoaded', function () {
    const spinner = document.getElementById('loading-spinner');
    const container = document.getElementById('notification-list');

    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.dataset.filter;

            // Show spinner and clear content
            spinner.classList.remove('hidden');
            container.innerHTML = '';

            fetch(`{{ route('pages.Notifications') }}?filter=${filter}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                container.innerHTML = html;
                spinner.classList.add('hidden');

                // Update tab styles
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('border-green-600', 'text-green-800');
                    btn.classList.add('border-transparent', 'text-gray-600');
                });

                this.classList.remove('border-transparent', 'text-gray-600');
                this.classList.add('border-green-600', 'text-green-800');
            });
        });
    });
});
</script>