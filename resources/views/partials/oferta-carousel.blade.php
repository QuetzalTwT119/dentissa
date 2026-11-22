@if ($ofertas->isNotEmpty())
    <div x-data="ofertaCarousel({{ $ofertas->count() }})"
         @mouseenter="isPaused = true"
         @mouseleave="isPaused = false"
         @focusin="isPaused = true"
         @focusout="isPaused = false"
         class="relative">
        <div x-ref="track" @scroll="handleScroll" class="no-scrollbar flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth pb-2">
            @foreach ($ofertas as $index => $oferta)
                @php
                    // Misma etiqueta que la portada y el panel (Oferta::etiquetaBeneficio).
                        $valor = $oferta->etiquetaBeneficio();
                @endphp
                <div class="w-[85%] shrink-0 snap-start overflow-hidden rounded-2xl border border-line bg-white shadow-soft transition duration-200 ease-out hover:border-peach-300 sm:w-[calc((100%-1.5rem)/2)] lg:w-[calc((100%-4.5rem)/4)]"
                     style="animation-delay: {{ $index * 60 }}ms">
                    @if ($oferta->imagen)
                        <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="h-40 w-full object-cover">
                    @else
                        <div class="flex h-40 w-full items-center justify-center bg-brand-50 text-xs font-semibold text-brand-400">
                            Pendiente de imagen
                        </div>
                    @endif
                    <div class="p-6">
                        <h3 class="font-semibold text-ink">{{ $oferta->titulo }}</h3>
                        @if ($valor)
                            <p class="mt-2 text-sm font-semibold text-brand-600">{{ $valor }}</p>
                        @endif
                        <p class="mt-2 text-xs text-muted">
                            Vigente hasta {{ \Illuminate\Support\Carbon::parse($oferta->fecha_fin)->translatedFormat('j \d\e F \d\e Y') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($ofertas->count() > 1)
            <button type="button" @click="prev()" aria-label="Oferta anterior"
                    class="absolute left-2 top-1/2 hidden -translate-y-1/2 items-center justify-center rounded-full bg-white/90 p-2 text-brand-600 shadow-soft transition hover:bg-white hover:text-brand-700 sm:flex">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button type="button" @click="next()" aria-label="Siguiente oferta"
                    class="absolute right-2 top-1/2 hidden -translate-y-1/2 items-center justify-center rounded-full bg-white/90 p-2 text-brand-600 shadow-soft transition hover:bg-white hover:text-brand-700 sm:flex">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div class="mt-4 flex justify-center gap-2">
                @foreach ($ofertas as $index => $oferta)
                    <button type="button" @click="goTo({{ $index }})" aria-label="Ir a la oferta {{ $index + 1 }}" class="flex h-4 w-4 items-center justify-center">
                        <span class="block h-2 rounded-full transition duration-200" :class="activeIndex === {{ $index }} ? 'w-6 bg-brand-500' : 'w-2 bg-brand-200 hover:bg-brand-300'"></span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
@endif
