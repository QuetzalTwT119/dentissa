@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegación de páginas" class="inline-flex flex-wrap items-center justify-center gap-1 text-xs">
        {{-- Enlace a la página anterior --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Página anterior deshabilitada" class="inline-flex items-center gap-1 rounded-xl border border-line/60 bg-[#faf7f7] px-2.5 py-1.5 text-xs font-semibold text-[#a59599] opacity-60 cursor-not-allowed select-none">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Anterior</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Ir a la página anterior" class="inline-flex items-center gap-1 rounded-xl border border-line bg-white px-2.5 py-1.5 text-xs font-semibold text-ink transition-all hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Anterior</span>
            </a>
        @endif

        {{-- Elementos de paginación (números y elipsis) --}}
        @foreach ($elements as $element)
            {{-- Separador de puntos suspensivos --}}
            @if (is_string($element))
                <span aria-disabled="true" class="px-2 py-1.5 text-xs font-semibold text-muted select-none">{{ $element }}</span>
            @endif

            {{-- Enlaces a números de página --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" aria-label="Página actual, página {{ $page }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl border border-brand-500 bg-brand-500 px-2.5 text-xs font-bold text-white shadow-xs">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-xl border border-line bg-white px-2.5 text-xs font-semibold text-ink transition-all hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Enlace a la página siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Ir a la página siguiente" class="inline-flex items-center gap-1 rounded-xl border border-line bg-white px-2.5 py-1.5 text-xs font-semibold text-ink transition-all hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-1">
                <span>Siguiente</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        @else
            <span aria-disabled="true" aria-label="Página siguiente deshabilitada" class="inline-flex items-center gap-1 rounded-xl border border-line/60 bg-[#faf7f7] px-2.5 py-1.5 text-xs font-semibold text-[#a59599] opacity-60 cursor-not-allowed select-none">
                <span>Siguiente</span>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
        @endif
    </nav>
@endif
