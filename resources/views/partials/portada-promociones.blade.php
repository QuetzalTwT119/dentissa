{{--
    Ofertas vigentes en la portada. Antes las ofertas se cargaban pero no se
    mostraban en ningún lado (revisión del 20/11/2026). Solo aparece si hay
    alguna activa dentro de sus fechas (la consulta vive en routes/web.php).

    21/11/2026: tarjetas compactas en cuadrícula (4 por fila en PC), sin
    carrusel: la portada ya tiene dos y las ofertas se comparan mejor a la vista.
--}}
@if ($ofertas->isNotEmpty())
    <section id="promociones" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20" aria-labelledby="titulo-promociones">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow justify-center">Promociones</p>
            <h2 id="titulo-promociones" class="mt-4 text-title-1 text-ink">Ofertas</h2>
            <p class="mt-3 text-callout text-muted">Por tiempo limitado. El pago se realiza en el consultorio el día de tu cita.</p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($ofertas as $oferta)
                @php
                    $quedan = $oferta->lugaresQuedan();
                @endphp
                <article class="card-boutique flex h-full flex-col overflow-hidden" data-oferta-publica>
                    {{-- La imagen que se sube en el panel (antes se pedía pero no se mostraba). --}}
                    @if ($oferta->imagen)
                        <img src="/storage/{{ $oferta->imagen }}" alt="" loading="lazy" class="h-28 w-full object-cover">
                    @endif
                    <div class="flex flex-1 flex-col p-5">
                        <span class="self-start rounded-full bg-brand-600 px-2.5 py-0.5 text-sm font-semibold text-white">{{ $oferta->etiquetaBeneficio() }}</span>
                        <h3 class="mt-3 font-serif text-lg font-semibold leading-snug text-ink">{{ $oferta->titulo }}</h3>
                        @if ($oferta->tratamiento)
                            <p class="mt-0.5 text-sm font-medium text-brand-700">{{ $oferta->tratamiento->nombre }}</p>
                        @endif
                        @if (filled($oferta->descripcion))
                            <p class="mt-2 line-clamp-2 text-sm text-muted">{{ $oferta->descripcion }}</p>
                        @endif
                        <div class="mt-auto flex flex-wrap items-end justify-between gap-x-3 gap-y-1 pt-4">
                            <p class="text-xs text-ink-soft">
                                Hasta el {{ $oferta->fecha_fin->locale('es')->translatedFormat('j \d\e F') }}
                                @if ($quedan !== null)
                                    <br>{{ $quedan === 1 ? 'Queda 1 lugar' : "Quedan {$quedan} lugares" }}
                                @endif
                            </p>
                            <a href="{{ route('citas.publica.create', array_filter(['tratamiento' => $oferta->id_tratamiento])) }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-brand-700 hover:text-brand-800" aria-label="Agendar cita: {{ $oferta->titulo }}">
                                Agendar {!! $iconoFlecha !!}
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
