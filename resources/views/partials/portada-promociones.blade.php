{{--
    Promociones vigentes en la portada. Antes las ofertas se cargaban pero no se
    mostraban en ningún lado (revisión del 20/11/2026). Solo aparece si hay
    alguna activa dentro de sus fechas (la consulta vive en routes/web.php).
--}}
@if ($ofertas->isNotEmpty())
    <section id="promociones" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-24" aria-labelledby="titulo-promociones">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow justify-center">Promociones</p>
            <h2 id="titulo-promociones" class="mt-4 text-title-1 text-ink">Beneficios vigentes</h2>
            <p class="mt-4 text-callout text-muted">Por tiempo limitado. El pago se realiza en el consultorio el día de tu cita.</p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($ofertas as $oferta)
                @php
                    $valor = (float) $oferta->valor_descuento;
                    // tipo y tipo_valor son enums en el modelo: se compara su valor.
                    $tipoValor = $oferta->tipo_valor?->value ?? $oferta->tipo_valor;
                    $tipo = $oferta->tipo?->value ?? $oferta->tipo;
                    $etiqueta = match (true) {
                        $oferta->precio_especial > 0 => 'Precio especial $'.number_format((float) $oferta->precio_especial, fmod((float) $oferta->precio_especial, 1.0) == 0.0 ? 0 : 2),
                        $valor > 0 && $tipoValor === 'porcentaje' => '−'.rtrim(rtrim(number_format($valor, 2), '0'), '.').'%',
                        $valor > 0 => '−$'.number_format($valor, fmod($valor, 1.0) == 0.0 ? 0 : 2),
                        $tipo === 'paquete' => 'Paquete',
                        default => 'Beneficio',
                    };
                    $quedan = $oferta->cupo_total > 0 ? max(0, $oferta->cupo_total - $oferta->cupos_utilizados) : null;
                @endphp
                <article class="card-boutique flex h-full flex-col p-6 sm:p-7" data-oferta-publica>
                    <span class="self-start rounded-full bg-brand-600 px-3 py-1 text-sm font-semibold text-white">{{ $etiqueta }}</span>
                    <h3 class="mt-4 font-serif text-title-3 text-ink">{{ $oferta->titulo }}</h3>
                    @if ($oferta->tratamiento)
                        <p class="mt-1 text-sm font-medium text-brand-700">{{ $oferta->tratamiento->nombre }}</p>
                    @endif
                    @if (filled($oferta->descripcion))
                        <p class="mt-2 line-clamp-3 text-callout text-muted">{{ $oferta->descripcion }}</p>
                    @endif
                    <p class="mt-auto pt-5 text-sm text-ink-soft">
                        Válida hasta el {{ $oferta->fecha_fin->locale('es')->translatedFormat('j \d\e F') }}
                        @if ($quedan !== null)
                            · {{ $quedan === 1 ? 'Queda 1 lugar' : "Quedan {$quedan} lugares" }}
                        @endif
                    </p>
                    <a href="{{ route('citas.publica.create') }}" class="mt-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-800">
                        Agendar cita {!! $iconoFlecha !!}
                    </a>
                </article>
            @endforeach
        </div>
    </section>
@endif
