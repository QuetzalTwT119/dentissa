{{--
    Página pública con todas las promociones (25/11/2026). Misma tarjeta que el
    carrusel de la portada (partials.tarjeta-oferta) y filtro por especialidad
    con enlaces normales: funciona sin JavaScript y se puede compartir la URL.
--}}
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fbf7f3">
        <meta name="description" content="Promociones vigentes en Dentissa, consultorio de la Dra. Melissa López. Agenda en línea con la promoción aplicada.">
        @include('partials.favicon')

        <title>Promociones — Dentissa Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-cream font-sans text-ink antialiased">
        <x-asistente-chat />

        <header class="sticky top-0 z-20 border-b border-line/70 bg-cream/90 pt-[env(safe-area-inset-top)] backdrop-blur-md supports-[backdrop-filter]:bg-cream/75">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
                <x-marca :href="url('/')" aria-label="Volver al inicio" />

                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-full border border-line bg-white px-3 text-sm font-semibold text-ink transition-colors hover:border-peach-300 hover:text-brand-700 sm:px-4" aria-label="Volver al inicio">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        <span class="hidden sm:inline">Inicio</span>
                    </a>
                    <a href="{{ route('citas.publica.create') }}" class="btn-brand inline-flex min-h-11 items-center px-4 text-sm sm:px-5">Agendar cita</a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 pb-20 pt-10 sm:px-6 lg:px-8 lg:pt-14" data-pagina-promociones>
            <div class="max-w-2xl">
                <p class="eyebrow">Promociones</p>
                <h1 class="mt-4 text-title-1 text-ink">Todas las promociones</h1>
                <p class="mt-3 text-callout text-muted">
                    Por tiempo limitado. Toca «Agendar» y la promoción queda aplicada a tu cita; el pago se realiza en el consultorio el día de tu cita.
                </p>
            </div>

            @if ($especialidades->count() > 1)
                <nav aria-label="Filtrar por especialidad" class="-mx-4 mt-8 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden" data-filtro-especialidad>
                    @php
                        $chip = 'inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 text-sm font-semibold transition-colors';
                        $activo = 'border-brand-600 bg-brand-600 text-white';
                        $inactivo = 'border-line bg-white text-ink hover:border-peach-300 hover:text-brand-700';
                    @endphp
                    <a href="{{ route('promociones.index') }}" class="{{ $chip }} {{ $elegida ? $inactivo : $activo }}" @if (! $elegida) aria-current="page" @endif>Todas</a>
                    @foreach ($especialidades as $especialidad)
                        <a href="{{ route('promociones.index', ['especialidad' => $especialidad->id]) }}" class="{{ $chip }} {{ $elegida?->id === $especialidad->id ? $activo : $inactivo }}" @if ($elegida?->id === $especialidad->id) aria-current="page" @endif>{{ $especialidad->nombre }}</a>
                    @endforeach
                </nav>
            @endif

            <p class="mt-6 text-sm text-ink-soft" role="status" data-total-promociones>
                {{ $ofertas->count() === 1 ? '1 promoción vigente' : $ofertas->count().' promociones vigentes' }}{{ $elegida ? ' en '.$elegida->nombre : '' }}
            </p>

            @if ($ofertas->isEmpty())
                <div class="mt-6 rounded-2xl border border-line bg-white p-8 text-center shadow-soft" data-sin-promociones>
                    <p class="font-semibold text-ink">Por ahora no hay promociones vigentes.</p>
                    <p class="mt-2 text-callout text-muted">Puedes agendar tu valoración en línea o escribirnos por WhatsApp.</p>
                    <a href="{{ route('citas.publica.create') }}" class="btn-brand mt-6 inline-flex min-h-11 items-center px-6">Agendar cita</a>
                </div>
            @else
                <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ofertas as $oferta)
                        @include('partials.tarjeta-oferta')
                    @endforeach
                </div>
            @endif
        </main>

        <footer class="border-t border-line/70 bg-white py-8 pb-[max(2rem,env(safe-area-inset-bottom))]">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 text-sm text-ink-soft sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>Dentissa · Dra. Melissa López N. · {{ $contacto['direccion'] }}</p>
                <a href="{{ $contacto['telefono_enlace'] }}" class="inline-flex min-h-11 items-center font-semibold text-brand-700 hover:text-brand-800">{{ $contacto['telefono'] }}</a>
            </div>
        </footer>
    </body>
</html>
