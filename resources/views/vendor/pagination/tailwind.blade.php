    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <script src="{{ mix('js/app.js') }}" defer></script>
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between mt-6">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">
                ‹ Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-sky-500 hover:bg-sky-600 rounded-md transition">
                ‹ Prev
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="hidden sm:flex space-x-1">
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="px-3 py-1.5 text-sm text-gray-500">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="px-3 py-1.5 text-sm font-semibold text-white bg-sky-600 rounded-md">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 text-sm text-gray-700 hover:bg-sky-100 rounded-md transition">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-sky-500 hover:bg-sky-600 rounded-md transition">
                Next ›
            </a>
        @else
            <span class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">
                Next ›
            </span>
        @endif
    </nav>
@endif
