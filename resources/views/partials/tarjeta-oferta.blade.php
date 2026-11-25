{{--
    Tarjeta de una oferta para el público (25/11/2026). La usan el carrusel de la
    portada y la página /promociones, así las dos se ven y se comportan igual.

    Recibe: $oferta (App\Models\Oferta, con lugares usados cargados) y $contacto
    (config('dentissa.contacto'), para el WhatsApp).
--}}
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
                    Agendar <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </div>
</article>
