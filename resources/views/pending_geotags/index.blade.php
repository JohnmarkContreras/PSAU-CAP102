@extends('layouts.app')
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@section('content')
<div x-data="{ showModal: false, modalImg: '' }">
<section class="bg-[#e9eee9] rounded-lg p-4 relative">
    <x-card title="Pending Geotag Trees">
        <div class="text-sm text-black/90 space-y-0.5">
            @if($pending->isEmpty())
                <p>No pending geotags.</p>
            @else
                <div 
                    x-data="paginationComponent()" 
                    x-init="init()" 
                    class="overflow-x-auto min-h-[600px]"
                >
                    <div x-html="tableContent"></div>
                </div>
            @endif
        </div>
    </x-card>
</section>

<!-- Modal for image preview -->
<div 
    x-show="showModal" 
    x-transition.opacity
    class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50"
    @click.away="showModal = false"
    @keydown.escape.window="showModal = false"
    style="display: none;"
>
    <div class="relative">
        <img :src="modalImg" class="max-h-[80vh] max-w-[90vw] rounded shadow-lg border-4 border-white" alt="Preview">
        <button 
            @click="showModal = false"
            class="absolute top-2 right-2 text-white text-3xl font-bold bg-black bg-opacity-50 rounded-full px-2"
            aria-label="Close"
        >&times;</button>
    </div>
</div>
</div>
@endsection

<script>
    function paginationComponent() {
    return {
        tableContent: '',
        init() {
            this.loadPage("{{ route('pending-geotags.index') }}");
        },
        loadPage(url) {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    this.tableContent = html;

                    // Rebind pagination links inside the newly injected HTML
                    this.$nextTick(() => {
                        this.$root.querySelectorAll('.pagination a').forEach(link => {
                            link.addEventListener('click', (e) => {
                                e.preventDefault();
                                this.loadPage(link.href); 
                            });
                        });
                    });
                });
        }
    }
}

</script>