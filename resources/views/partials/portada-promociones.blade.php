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
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
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
                    @php
                        $quedan = $oferta->lugaresQuedan();
                    @endphp
                    <article class="card-boutique flex h-full flex-col overflow-hidden" data-oferta-publica>
                        {{-- La imagen que se sube en el panel. --}}
                        @if ($oferta->imagen)
                            <img src="/storage/{{ $oferta->imagen }}" alt="" loading="lazy" class="h-32 w-full object-cover">
                        @endif
                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <span class="self-start rounded-full bg-brand-600 px-2.5 py-0.5 text-sm font-semibold text-white">{{ $oferta->etiquetaBeneficio() }}</span>
                            <h3 class="mt-3 font-serif text-title-3 leading-snug text-ink">{{ $oferta->titulo }}</h3>
                            @if ($oferta->tratamiento)
                                <p class="mt-0.5 text-sm font-medium text-brand-700">{{ $oferta->tratamiento->nombre }}</p>
                            @endif
                            @if (filled($oferta->descripcion))
                                {{-- Completa: el paciente debe poder leer qué incluye la oferta. --}}
                                <p class="mt-2 text-callout text-muted" data-descripcion-oferta>{{ $oferta->descripcion }}</p>
                            @endif
                            <div class="mt-auto flex flex-wrap items-end justify-between gap-x-3 gap-y-1 pt-5">
                                <p class="text-xs text-ink-soft">
                                    Hasta el {{ $oferta->fecha_fin->locale('es')->translatedFormat('j \d\e F') }}
                                    @if ($quedan !== null)
                                        <br>{{ $quedan === 1 ? 'Queda 1 lugar' : "Quedan {$quedan} lugares" }}
                                    @endif
                                </p>
                                <div class="flex items-center gap-4">
                                    {{-- 24/11/2026: dudas de la oferta por WhatsApp, con el nombre ya escrito. --}}
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $contacto['whatsapp']) }}?text={{ rawurlencode('Hola, quisiera información sobre la oferta «'.$oferta->titulo.'».') }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex min-h-11 items-center text-sm font-semibold text-sage-700 hover:text-sage-800" aria-label="Pedir información por WhatsApp: {{ $oferta->titulo }}" data-oferta-whatsapp>Pedir info</a>
                                    <a href="{{ route('citas.publica.create', array_filter(['tratamiento' => $oferta->id_tratamiento, 'oferta' => $oferta->id_tratamiento ? $oferta->id : null])) }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800" aria-label="Agendar cita: {{ $oferta->titulo }}">
                                    Agendar {!! $iconoFlecha !!}
                                </a>
                                </div>
                            </div>
                        </div>
                    </article>
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
    </div>
    </section>
@endif
