{{--
    Ubicación con mapa de Google (24/11/2026). La dirección sale de
    Configuración → Datos del consultorio (config dentissa.contacto.direccion).
    El mapa es el embebido público de Google Maps: no necesita clave de API.
    Se carga solo al acercarse (loading="lazy") para no frenar la portada.
--}}
@php
    $direccion = trim((string) ($contacto['direccion'] ?? ''));
    $direccionUrl = urlencode($direccion);
@endphp
@if ($direccion !== '')
    <section id="ubicacion" aria-labelledby="titulo-ubicacion" class="mx-auto max-w-7xl px-4 pt-20 sm:px-6 lg:px-8 lg:pt-28" data-ubicacion>
        <div class="max-w-2xl">
            <p class="eyebrow">Ubicación</p>
            <h2 id="titulo-ubicacion" class="mt-4 text-title-1 text-ink">Visítanos en el consultorio</h2>
        </div>
        <div class="mt-10 grid overflow-hidden rounded-[2rem] border border-line bg-white shadow-soft lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div class="flex flex-col justify-center gap-5 p-6 sm:p-10">
                <address class="flex items-start gap-3 not-italic text-body text-ink-soft">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657 13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span data-direccion>{{ $direccion }}</span>
                </address>
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $direccionUrl }}" target="_blank" rel="noopener noreferrer"
                       class="btn-brand min-h-11 justify-center px-6" data-como-llegar>
                        Cómo llegar
                        <span class="sr-only">(se abre en Google Maps)</span>
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $contacto['whatsapp']) }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-11 items-center justify-center rounded-full border border-line bg-white px-6 text-sm font-semibold text-ink transition-colors hover:border-peach-300 hover:text-brand-700">
                        Escribir por WhatsApp
                    </a>
                </div>
            </div>
            <iframe src="https://maps.google.com/maps?q={{ $direccionUrl }}&amp;z=16&amp;output=embed"
                    title="Mapa: {{ $direccion }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    class="h-72 w-full border-0 border-t border-line sm:h-96 lg:h-full lg:min-h-[24rem] lg:border-l lg:border-t-0"
                    data-mapa></iframe>
        </div>
    </section>
@endif
