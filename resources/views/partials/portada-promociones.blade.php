{{--
    Ofertas vigentes en la portada. Solo aparece si hay alguna publicada dentro
    de sus fechas (Oferta::paraPortada()).

    22/11/2026: carrusel, con el mismo componente global que Especialidades y
    Testimonios (carruselServicios): 3 tarjetas por vista en PC, 2 en tableta,
    flechas y puntos. Antes era una cuadrícula de 4 por fila: con pocas
    ofertas quedaba cargada a la izquierda y con muchas alargaba la portada
    (solo cabían 12). La descripción se muestra completa (antes se cortaba
    en dos renglones con "…"; mide como máximo 300 caracteres).
--}}
@if ($ofertas->isNotEmpty())
    <section id="promociones" class="border-y border-line/80 bg-white" aria-labelledby="titulo-promociones">
    <div class="mx-auto max-w-7xl 2xl:max-w-[88rem] px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="max-w-2xl">
            <p class="eyebrow">Promociones</p>
            <h2 id="titulo-promociones" class="mt-4 text-title-1 text-ink">Ofertas</h2>
            <p class="mt-3 text-callout text-muted">Por tiempo limitado. El pago se realiza en el consultorio el día de tu cita.</p>
        </div>

        <div x-data="carruselServicios" class="relative mt-10" role="region" aria-roledescription="carrusel" aria-label="Ofertas vigentes" data-carrusel-ofertas>
            <button type="button" x-show="posiciones > 1" x-on:click="anterior()" class="carrusel-flecha carrusel-flecha-izq focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Ofertas anteriores">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <div x-ref="pista" x-on:scroll.passive="alDesplazar()" tabindex="0" @class(['carrusel-pista focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400', 'carrusel-pista--pocas' => $ofertas->count() <= 2]) aria-label="Ofertas; desliza para ver más">
                @foreach ($ofertas as $oferta)
                    {{-- 25/11/2026: la misma tarjeta que la página /promociones. --}}
                    @include('partials.tarjeta-oferta')
                @endforeach
            </div>

            <button type="button" x-show="posiciones > 1" x-on:click="siguiente()" class="carrusel-flecha carrusel-flecha-der focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Más ofertas">
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>

            <div x-show="posiciones > 1" class="mt-5 flex items-center justify-center" aria-hidden="true">
                <template x-for="i in posiciones" :key="i">
                    <button type="button" tabindex="-1" x-on:click="ir(i - 1)" class="flex h-6 items-center px-1">
                        <span class="h-2 rounded-full transition-[width,background-color,opacity] duration-200 ease-out"
                              :class="actual === i - 1 ? 'w-[1.375rem] bg-brand-600' : 'w-2 bg-peach-300 opacity-70 hover:opacity-100'"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- 25/11/2026: el carrusel muestra como máximo Oferta::MAXIMO_PORTADA; aquí se ven todas. --}}
        <div class="mt-8 flex justify-center">
            <a href="{{ route('promociones.index') }}" class="btn-secondary inline-flex min-h-11 items-center gap-1.5 px-6" data-ver-todas-ofertas>
                Ver todas las promociones{{ ($totalOfertas ?? 0) > $ofertas->count() ? ' ('.$totalOfertas.')' : '' }}
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>
    </div>
    </section>
@endif
