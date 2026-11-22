<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" content="#fbf7f3">
    @include('partials.favicon')
    <title>Dentissa — Odontología Integral | Dra. Melissa López N.</title>

    <!-- Tipografía local: Figtree (texto), Fraunces (títulos), Fraunces itálica (firma) -->
    <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/fraunces-latin-500-italic.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Tailwind CSS y Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        html {
            scroll-padding-top: 5.5rem;
        }
        #inicio, #servicios, #nosotros, #galeria, #testimonios, #contacto {
            scroll-margin-top: 5.5rem;
        }
        .landing-nav-link[aria-current="location"] {
            color: #a12a54;
        }
        .landing-nav-link[aria-current="location"]::after {
            transform: scaleX(1);
        }
        .landing-mobile-nav-link[aria-current="location"] {
            color: #a12a54;
            background-color: #fdf1f4;
        }
    </style>
</head>
<body class="bg-cream font-sans text-ink antialiased">
    <x-asistente-chat />

    @php
        $iconoCalendario = '<svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
        $iconoFlecha = '<svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/></svg>';

        $enlacesSecciones = [
            ['#inicio', 'Inicio'],
            ['#servicios', 'Tratamientos'],
            ['#nosotros', 'La Clínica'],
            ['#galeria', 'Instalaciones'],
            ['#testimonios', 'Testimonios'],
            ['#contacto', 'Contacto'],
        ];

        $urlPortal = Route::has('login') ? (auth()->check() ? url('/dashboard') : route('login')) : url('/login');
        $textoPortal = auth()->check() ? 'Dashboard' : 'Portal Paciente';

        $beneficios = [
            [
                'titulo' => 'Atención Humana y Sin Prisas',
                'texto' => 'Cada consulta está programada con el tiempo exacto para escucharte, resolver dudas y atenderte con calma.',
                'icono' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            ],
            [
                'titulo' => 'Instrumental Estéril & Preciso',
                'texto' => 'Bioseguridad de nivel clínico con instrumental empaquetado y tecnología para procedimientos cómodos.',
                'icono' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            ],
            [
                'titulo' => 'Diagnóstico Transparente',
                'texto' => 'Explicación clara con tu expediente clínico digital, sin tratamientos innecesarios ni costos ocultos.',
                'icono' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            ],
        ];

        $tratamientos = [
            ['01', 'Valoración Integral', 'Revisión clínica completa, exploración intraoral y diagnóstico personalizado con un enfoque preventivo y cuidadoso.', '30 min', 'Enfoque', 'Diagnóstico claro', false],
            ['02', 'Limpieza Dental Profunda', 'Eliminación de sarro y placa mediante ultrasonido suave, profilaxis y pulido para encías sanas y aliento fresco.', '1 hora', 'Frecuencia', 'Cada 6 meses', false],
            ['03', 'Blanqueamiento Dental', 'Tecnología aclarante de alta eficacia para devolver la luminosidad natural a tus dientes sin desgastar el esmalte.', '1.5 horas', 'Resultados', 'Desde 1 sesión', true],
            ['04', 'Retiro de Caries & Resinas', 'Restauraciones estéticas y biocompatibles del color exacto de tu diente, devolviendo forma y funcionalidad.', '1 hora', 'Materiales', 'Resina estética', false],
            ['05', 'Extracción Cuidadosa', 'Procedimiento mínimamente invasivo con anestesia precisa para una recuperación rápida, cómoda y segura.', '1 hora', 'Enfoque', 'Sin dolor', false],
        ];

        $compromisos = [
            ['Historial clínico digital:', 'Información gestionada de forma confidencial dentro del sistema de la clínica.'],
            ['Bioseguridad y esterilización de grado clínico:', 'Instrumental empacado y esterilizado individualmente, abierto siempre ante tu presencia.'],
            ['Puntualidad en tu agenda:', 'Bloqueo dinámico de horarios para evitar esperas innecesarias en sala de recepción.'],
        ];

    @endphp

    <!-- ============ Navegación ============ -->
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 pt-[env(safe-area-inset-top)] border-b border-line/70 bg-cream/90 backdrop-blur-md supports-[backdrop-filter]:bg-cream/75">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="flex h-[4.5rem] items-center justify-between gap-4" aria-label="Navegación principal">

                <!-- Marca global (x-marca) -->
                <x-marca :href="url('/')" aria-label="Dra. Melissa López, Odontología Integral: ir al inicio" />

                <!-- Menú de escritorio (>= 1024px) -->
                <div class="hidden items-center gap-7 whitespace-nowrap text-sm font-medium text-ink-soft xl:flex">
                    @foreach ($enlacesSecciones as [$href, $texto])
                        <a href="{{ $href }}" data-scrollspy-link @if ($loop->first) aria-current="location" @endif class="landing-nav-link relative py-2 transition-colors hover:text-ink after:absolute after:inset-x-0 after:-bottom-0.5 after:h-px after:origin-left after:scale-x-0 after:bg-brand-500 after:transition-transform after:duration-200">{{ $texto }}</a>
                    @endforeach
                </div>

                <!-- Acciones -->
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ $urlPortal }}" class="hidden min-h-11 items-center rounded-full px-4 text-sm font-semibold text-ink-soft transition-colors hover:text-brand-700 sm:inline-flex">{{ $textoPortal }}</a>

                    <a href="{{ route('citas.publica.create') }}" class="btn-brand !hidden sm:!inline-flex">
                        {!! $iconoCalendario !!}
                        <span>Agendar Cita</span>
                    </a>

                    <!-- Botón de menú (móvil y tablet) -->
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            :aria-expanded="mobileMenuOpen.toString()"
                            :aria-label="mobileMenuOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'"
                            aria-controls="mobile-navigation-menu"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-line bg-white text-ink transition-colors hover:text-brand-700 xl:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen}" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Menú móvil desplegable -->
        <div id="mobile-navigation-menu"
             x-cloak
             x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-out duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click.outside="mobileMenuOpen = false"
             @keydown.escape.window="mobileMenuOpen = false"
             class="max-h-[calc(100dvh-4.5rem)] overflow-y-auto overscroll-contain border-t border-line bg-cream px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-3 shadow-lift xl:hidden">

            <nav class="flex flex-col gap-1 text-base font-medium text-ink" aria-label="Menú móvil">
                @foreach ($enlacesSecciones as [$href, $texto])
                    <a href="{{ $href }}" data-scrollspy-link @if ($loop->first) aria-current="location" @endif @click="mobileMenuOpen = false" class="landing-mobile-nav-link flex min-h-12 items-center justify-between rounded-2xl px-4 transition-colors hover:bg-white">
                        <span>{{ $texto }}</span>
                        <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </nav>

            <div class="mt-4 grid gap-2.5 border-t border-line pt-4">
                <a href="{{ route('citas.publica.create') }}" @click="mobileMenuOpen = false" class="btn-brand w-full py-3">
                    {!! $iconoCalendario !!}
                    <span>Agendar Cita</span>
                </a>
                <a href="{{ $urlPortal }}" @click="mobileMenuOpen = false" class="btn-secondary w-full py-3">{{ $textoPortal }}</a>
            </div>
        </div>
    </header>

    <main>
        <!-- ============ Hero ============ -->
        {{--
            "Panorámica": la foto real del consultorio ocupa la derecha y su orilla
            izquierda se funde con la crema; el texto vive en el 40 % izquierdo para
            no quedar nunca encima de la doctora ni de la paciente. En celular y
            tableta la foto va arriba y el texto debajo.
        --}}
        <section id="inicio" aria-labelledby="titulo-inicio" class="relative overflow-hidden lg:flex lg:min-h-[min(46rem,calc(100dvh-4.5rem))] lg:items-center">
            <div class="relative aspect-[4/3] sm:aspect-[16/9] lg:absolute lg:inset-y-0 lg:left-[44%] lg:right-0 lg:aspect-auto">
                <img src="{{ asset('images/dentissa/hero-inicio.jpg') }}"
                     alt="La doctora conversa con una paciente sentada en el sillón dental del consultorio"
                     width="1376" height="768" fetchpriority="high"
                     class="absolute inset-0 h-full w-full object-cover object-[70%_40%] lg:object-[72%_40%]">
                <div aria-hidden="true" class="absolute inset-0 hidden bg-[linear-gradient(90deg,rgb(var(--c-cream))_0%,rgb(var(--c-cream)/0.7)_12%,rgb(var(--c-cream)/0)_32%)] lg:block"></div>
            </div>

            <div class="relative mx-auto w-full max-w-7xl px-4 pb-14 pt-8 sm:px-6 sm:pt-10 lg:px-8 lg:py-20">
                <div class="max-w-xl lg:max-w-[min(34rem,40%)]">
                    <p class="hero-entra eyebrow">Odontología integral y estética</p>

                    <h1 id="titulo-inicio" class="hero-entra mt-5 max-w-[13ch] text-display text-ink [animation-delay:50ms]">
                        Una consulta <em class="font-medium italic text-brand-600">sin prisas</em>, explicada paso a paso.
                    </h1>

                    <p class="hero-entra mt-5 text-body text-ink-soft [animation-delay:100ms] sm:text-lead">
                        Te escuchamos, te explicamos tu diagnóstico con calma y decides sin presión. Así se siente ir al dentista con la Dra. Melissa López.
                    </p>
                </div>

                <div class="hero-entra mt-8 flex flex-wrap items-center gap-x-6 gap-y-3 [animation-delay:150ms]">
                    <a href="{{ route('citas.publica.create') }}" class="btn-brand group px-7 py-3.5 text-callout">
                        <span>Agendar mi valoración</span>
                        <span class="transition-transform duration-200 ease-out group-hover:translate-x-0.5">{!! $iconoFlecha !!}</span>
                    </a>
                    <a href="{{ $contacto['telefono_enlace'] }}" class="inline-flex min-h-11 flex-col justify-center rounded-lg text-footnote text-muted transition-colors hover:text-brand-700">
                        ¿Prefieres llamar?
                        <span class="text-callout font-semibold tabular-nums text-ink">{{ $contacto['telefono'] }}</span>
                    </a>
                </div>

                @if ($estadoConsultorio)
                    <p class="hero-entra mt-7 inline-flex items-center gap-2 text-sm text-ink-soft [animation-delay:200ms]">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $estadoConsultorio['abierto'] ? 'bg-sage-500' : 'bg-field' }}" aria-hidden="true"></span>
                        {{ $estadoConsultorio['texto'] }}
                    </p>
                @endif
            </div>
        </section>

        <!-- ============ Beneficios ============ -->
        <section aria-label="Beneficios" class="border-y border-line/80 bg-white">
            <div class="mx-auto grid max-w-7xl gap-px overflow-hidden px-4 sm:px-6 md:grid-cols-3 lg:px-8">
                @foreach ($beneficios as $beneficio)
                    <div class="flex items-start gap-4 py-7 md:px-6 md:py-10 md:first:pl-0 md:[&:not(:first-child)]:border-l md:[&:not(:first-child)]:border-line/80 [&:not(:first-child)]:border-t [&:not(:first-child)]:border-line/80 md:[&:not(:first-child)]:border-t-0">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $loop->last ? 'bg-sage-50 text-sage-600' : 'bg-peach-50 text-brand-600' }}">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="{{ $beneficio['icono'] }}"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-serif text-title-3 font-medium text-ink">{{ $beneficio['titulo'] }}</h3>
                            <p class="mt-1.5 text-callout text-muted">{{ $beneficio['texto'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- ============ Tratamientos ============ -->
        <section id="servicios" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="max-w-2xl">
                <p class="eyebrow">Cuidado Especializado</p>
                <h2 class="mt-4 text-title-1 text-ink">
                    Tratamientos diseñados para tu salud y armonía
                </h2>
                <p class="mt-4 text-body text-muted">
                    Sesiones programadas con tiempo y dedicación para brindarte una atención puntual, cómoda y sin prisas.
                </p>
            </div>

            @if ($servicios->isNotEmpty())
                {{--
                    Carrusel "Fila": una tarjeta con foto por especialidad activa. Las que
                    tienen tratamientos activos se agendan en línea (la agenda abre con esa
                    especialidad elegida); las demás llevan al teléfono del consultorio.
                --}}
                <p class="mt-3 max-w-2xl text-callout text-muted">
                    Elige una especialidad para ver qué incluye y agendar en línea{{ $servicios->contains(fn ($servicio) => $servicio->tratamientos->isEmpty()) ? '; las demás las agendamos contigo por teléfono' : '' }}.
                </p>

                <div x-data="carruselServicios" class="relative mt-10" role="region" aria-roledescription="carrusel" aria-label="Especialidades">
                    <button type="button" x-show="posiciones > 1" x-on:click="anterior()" class="carrusel-flecha carrusel-flecha-izq focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Especialidades anteriores">
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>

                    <div x-ref="pista" x-on:scroll.passive="alDesplazar()" tabindex="0" @class(['carrusel-pista focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400', 'carrusel-pista--pocas' => $servicios->count() <= 2]) aria-label="Especialidades; desliza para ver más">
                        @foreach ($servicios as $servicio)
                            @php
                                $enLinea = $servicio->tratamientos->isNotEmpty();
                                $cuantos = $servicio->tratamientos->count();
                            @endphp
                            <a href="{{ $enLinea ? route('citas.publica.create', ['servicio' => $servicio->id]) : $contacto['telefono_enlace'] }}"
                               data-servicio="{{ $servicio->id }}"
                               aria-label="{{ $servicio->nombre }}: {{ $enLinea ? 'agendar en línea' : 'agendar por teléfono' }}"
                               class="group relative isolate flex min-h-[23.75rem] flex-col justify-end overflow-hidden rounded-[1.75rem] p-6 text-white shadow-soft focus:outline-none focus-visible:ring-4 focus-visible:ring-brand-300">
                                <img src="{{ $servicio->fotoPublica() }}" alt="" loading="lazy" decoding="async"
                                     class="absolute inset-0 -z-20 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                                <span aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-[#2b1a20]/90 via-[#2b1a20]/35 to-[#2b1a20]/5"></span>

                                <span @class([
                                    'mb-auto self-start rounded-full px-2.5 py-1 text-caption font-semibold uppercase tracking-label',
                                    'bg-sage-600 text-white' => $enLinea,
                                    'bg-ink/80 text-white ring-1 ring-inset ring-white/20 backdrop-blur-sm' => ! $enLinea,
                                ])>{{ $enLinea ? 'Agenda en línea' : 'Agenda por teléfono' }}</span>

                                <h3 class="font-serif text-title-3 font-medium">{{ $servicio->nombre }}</h3>
                                @if ($descripcion = $servicio->descripcionPublica())
                                    <p class="mt-1.5 line-clamp-3 text-callout text-white/85">{{ $descripcion }}</p>
                                @endif
                                <span class="mt-3.5 inline-flex items-center gap-1.5 text-sm font-semibold text-peach-200">
                                    {{ $enLinea ? ($cuantos === 1 ? 'Ver tratamiento y agendar' : "Ver {$cuantos} tratamientos y agendar") : 'Llamar · '.$contacto['telefono'] }}
                                    <span class="transition-transform duration-200 ease-out group-hover:translate-x-0.5">{!! $iconoFlecha !!}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <button type="button" x-show="posiciones > 1" x-on:click="siguiente()" class="carrusel-flecha carrusel-flecha-der focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400" aria-label="Más especialidades">
                        <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    {{-- Un punto por posición; separación pareja también junto al activo (alargado). --}}
                    <div x-show="posiciones > 1" class="mt-5 flex items-center justify-center" aria-hidden="true">
                        <template x-for="i in posiciones" :key="i">
                            <button type="button" tabindex="-1" x-on:click="ir(i - 1)" class="flex h-6 items-center px-1">
                                <span class="h-2 rounded-full transition-[width,background-color,opacity] duration-200 ease-out"
                                      :class="actual === i - 1 ? 'w-[1.375rem] bg-brand-600' : 'w-2 bg-peach-300 opacity-70 hover:opacity-100'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            @else
            <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($tratamientos as [$numero, $titulo, $texto, $duracion, $etiqueta, $valor, $destacado])
                    <article @class([
                        'relative flex h-full flex-col rounded-[1.75rem] p-6 sm:p-7',
                        'card-boutique is-interactive' => ! $destacado,
                        'bg-brand-700 text-white shadow-lift' => $destacado,
                    ])>
                        @if ($destacado)
                            <span class="absolute right-6 top-6 rounded-full bg-white/15 px-3 py-1 text-caption font-semibold uppercase tracking-label text-white ring-1 ring-inset ring-white/25">Más solicitado</span>
                        @endif

                        <div class="flex items-center gap-3">
                            <span @class(['font-serif text-sm', 'text-brand-600' => ! $destacado, 'text-peach-200' => $destacado])>{{ $numero }}</span>
                            <span @class(['h-px w-8', 'bg-line' => ! $destacado, 'bg-white/30' => $destacado]) aria-hidden="true"></span>
                            <span @class(['inline-flex items-center gap-1 text-xs font-medium', 'text-muted' => ! $destacado, 'text-white/80' => $destacado])>
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $duracion }}
                            </span>
                        </div>

                        <h3 @class(['mt-6 font-serif text-title-3 font-medium', 'text-ink' => ! $destacado])>{{ $titulo }}</h3>
                        <p @class(['mt-3 flex-1 text-callout', 'text-muted' => ! $destacado, 'text-white/80' => $destacado])>{{ $texto }}</p>

                        <div @class(['mt-6 flex items-end justify-between gap-4 border-t pt-5', 'border-line/80' => ! $destacado, 'border-white/20' => $destacado])>
                            <div>
                                <span @class(['block text-caption font-semibold uppercase tracking-label', 'text-muted' => ! $destacado, 'text-white/80' => $destacado])>{{ $etiqueta }}</span>
                                <span class="mt-0.5 block text-sm font-semibold">{{ $valor }}</span>
                            </div>
                            <a href="{{ route('citas.publica.create') }}" @class([
                                'inline-flex min-h-11 items-center gap-1.5 rounded-full px-4 text-sm font-semibold transition-colors',
                                'bg-cream text-brand-700 hover:bg-brand-50' => ! $destacado,
                                'bg-white text-brand-700 hover:bg-peach-50' => $destacado,
                            ])>
                                Agendar {!! $iconoFlecha !!}
                            </a>
                        </div>
                    </article>
                @endforeach

                <!-- Tarjeta fotográfica: instrumental -->
                <article class="group relative flex min-h-[320px] flex-col justify-end overflow-hidden rounded-[1.75rem] p-6 text-white shadow-soft sm:p-7">
                    <img src="{{ asset('images/3.jpg') }}" alt="Instrumental Dental de Precisión" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                    <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-[#2b1a20]/90 via-[#2b1a20]/40 to-transparent"></div>

                    <div class="relative">
                        <span class="text-caption font-semibold uppercase tracking-label text-peach-200">Bioseguridad de Grado Clínico</span>
                        <h3 class="mt-2 font-serif text-title-3 font-medium">Instrumental Estéril Garantizado</h3>
                        <p class="mt-2 text-callout text-white/80">Cada set de instrumental es sellado y esterilizado individualmente para tu máxima protección.</p>
                        <a href="{{ route('citas.publica.create') }}" class="mt-4 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-peach-200 transition-colors hover:text-white">
                            Agendar consulta {!! $iconoFlecha !!}
                        </a>
                    </div>
                </article>
            </div>
            @endif
        </section>

        <!-- ============ La clínica ============ -->
        <section id="nosotros" class="overflow-x-clip border-y border-line/80 bg-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8 lg:py-28">

                <figure class="relative mx-auto w-full max-w-md lg:col-span-5 lg:max-w-none">
                    <div aria-hidden="true" class="absolute -inset-3 -z-0 rotate-[-2.5deg] rounded-[2.5rem] bg-peach-100"></div>
                    <div class="relative aspect-[4/5] overflow-hidden rounded-[2.25rem] shadow-lift">
                        <img src="{{ asset('images/4.jpg') }}" alt="Uniforme Dra. Melissa López N." class="h-full w-full object-cover" loading="lazy">
                        <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-[#2b1a20]/85 via-transparent to-transparent"></div>
                        <figcaption class="absolute inset-x-0 bottom-0 p-6 text-white">
                            <span class="text-caption font-semibold uppercase tracking-label text-peach-200">Directora Médica</span>
                            <span class="mt-1 block firma text-[1.75rem] leading-tight">Dra. Melissa López N.</span>
                            <span class="mt-1.5 block text-sm text-white/80">Odontología Integral & Armonización</span>
                        </figcaption>
                    </div>
                </figure>

                <div class="lg:col-span-7">
                    <div>
                        <p class="eyebrow">Nuestra Filosofía</p>
                        <h2 class="mt-4 text-title-1 text-ink">
                            Cuidado dental personalizado en un ambiente de absoluta serenidad
                        </h2>
                        <p class="mt-5 max-w-2xl text-body text-ink-soft">
                            En Dentissa rediseñamos la consulta dental para que olvides el estrés: iluminación cálida, instrumental suave y, sobre todo, una comunicación honesta y pausada en cada paso.
                        </p>
                    </div>

                    <ul class="mt-8 divide-y divide-line/80 border-y border-line/80">
                        @foreach ($compromisos as [$fuerte, $detalle])
                            <li class="flex items-start gap-4 py-5">
                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sage-50 text-sage-600 ring-1 ring-inset ring-sage-500/20">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                </span>
                                <p class="text-body text-ink-soft"><strong class="font-semibold text-ink">{{ $fuerte }}</strong> {{ $detalle }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        <!-- ============ Galería ============ -->
        <section id="galeria" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="eyebrow">Ambiente Boutique</p>
                    <h2 class="mt-4 text-title-1 text-ink">
                        Cada detalle pensado para que te sientas en casa
                    </h2>
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-5 md:grid-cols-5">
                @foreach ([
                    ['6.jpg', 'Detalles del consultorio', 'Espacios Diseñados para Ti', 'Calidez y Hospitalidad', 'Disfruta de una sala relajante y una atención pensada para tu total tranquilidad.', 'md:col-span-3'],
                    ['2.jpg', 'Identidad de marca Dentissa', 'Armonía & Estética', 'Paleta de Marca Relajante', 'Colores rosa y durazno cálidos que transmiten serenidad, confianza y bienestar.', 'md:col-span-2'],
                ] as [$imagen, $alt, $etiqueta, $titulo, $texto, $columnas])
                    <figure class="group relative min-h-[300px] overflow-hidden rounded-[2rem] bg-shell shadow-soft sm:min-h-[380px] {{ $columnas }}">
                        <img src="{{ asset('images/'.$imagen) }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                        <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-[#2b1a20]/85 via-[#2b1a20]/20 to-transparent"></div>
                        <figcaption class="absolute inset-x-0 bottom-0 p-6 text-white sm:p-7">
                            <span class="text-caption font-semibold uppercase tracking-label text-peach-200">{{ $etiqueta }}</span>
                            <span class="mt-1.5 block font-serif text-title-3">{{ $titulo }}</span>
                            <span class="mt-1.5 block max-w-md text-callout text-white/80">{{ $texto }}</span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>

        <!-- ============ Promociones (solo si hay vigentes) ============ -->
        @include('partials.portada-promociones')

        <!-- ============ Testimonios ============ -->
        @include('partials.portada-testimonios')

        <!-- ============ Llamada a agendar ============ -->
        <section id="agendar" class="px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div class="relative mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] bg-brand-700 px-6 py-14 text-center text-white shadow-lift sm:px-12 lg:py-20">
                <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-brand-500/50 blur-3xl"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-peach-300/30 blur-3xl"></div>

                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-label text-peach-200">Reserva Fácil & Rápida</p>
                    <h2 class="mx-auto mt-4 max-w-2xl text-title-1">
                        Comienza tu camino hacia una mejor sonrisa
                    </h2>
                    <p class="mx-auto mt-4 max-w-lg text-body text-white/80">
                        Elige el tratamiento, la fecha y el horario que mejor se adapten a tu día para tu valoración en Dentissa.
                    </p>

                    <a href="{{ route('citas.publica.create') }}" class="mt-8 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-white px-8 text-callout font-semibold text-brand-700 shadow-lg transition-[background-color,transform] duration-150 ease-out hover:bg-peach-50 active:scale-[0.97] sm:w-auto">
                        {!! $iconoCalendario !!}
                        <span>Elegir fecha y horario</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- ============ Pie de página ============ -->
    <footer id="contacto" class="bg-[#2b2224] pb-[max(2.5rem,env(safe-area-inset-bottom))] pt-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 border-b border-white/10 pb-12 sm:grid-cols-2 lg:grid-cols-4">

                <div class="space-y-4 sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/9.jpg') }}" alt="Logo Dentissa" class="h-11 w-11 shrink-0 rounded-full bg-white object-contain p-0.5">
                        <div>
                            <span class="block font-serif text-xl leading-none text-white">Dentissa</span>
                            <span class="mt-1 block firma text-lg leading-none text-peach-200">Dra. Melissa López</span>
                        </div>
                    </div>
                    <p class="max-w-sm text-callout text-white/65">
                        Atención odontológica integral, estética y preventiva en un ambiente cálido y seguro.
                    </p>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-label text-peach-200">Tratamientos</h3>
                    <ul class="mt-4 space-y-1 text-sm text-white/70">
                        @foreach (['Valoración Integral', 'Limpieza Profiláctica', 'Resinas Biocompatibles', 'Extracciones Suaves'] as $tratamiento)
                            <li><a href="#servicios" class="inline-flex min-h-11 items-center transition-colors hover:text-white">{{ $tratamiento }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-label text-peach-200">Horarios</h3>
                    <ul class="mt-4 space-y-2 text-sm text-white/70">
                        @forelse ($horarioSemanal ?? [] as $linea)
                            <li data-horario-publico>{{ $linea }}</li>
                        @empty
                            <li>Consulta disponibilidad al agendar</li>
                        @endforelse
                        <li class="font-semibold text-peach-200">Atención con previa cita</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-label text-peach-200">Contacto y citas</h3>
                    <ul class="mt-4 space-y-1 text-sm text-white/70">
                        <li><a href="{{ $contacto['telefono_enlace'] }}" class="inline-flex min-h-11 items-center transition-colors hover:text-white">{{ $contacto['telefono'] }}</a></li>
                        <li><a href="mailto:trabajout745@gmail.com" class="inline-flex min-h-11 items-center break-all transition-colors hover:text-white">trabajout745@gmail.com</a></li>
                    </ul>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $contacto['whatsapp']) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-full bg-sage-500 px-5 text-sm font-semibold text-white transition-colors hover:bg-sage-600">WhatsApp</a>
                        <a href="{{ url('/login') }}" class="inline-flex min-h-11 items-center rounded-full bg-white/10 px-5 text-sm font-semibold text-white transition-colors hover:bg-white/20">Portal →</a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 pt-6 text-center text-xs text-white/60 sm:flex-row sm:text-left">
                <p>© {{ date('Y') }} Dentissa &bull; Dra. Melissa López N. Todos los derechos reservados.</p>
                <p>Atención con previa cita · <a href="{{ route('privacidad.aviso') }}" class="inline-flex min-h-11 items-center underline underline-offset-4 transition-colors hover:text-white">Aviso de privacidad</a></p>
            </div>
        </div>
    </footer>

    <script>
        const scrollSpyLinks = [...document.querySelectorAll('[data-scrollspy-link]')];
        const scrollSpySections = [...new Map(scrollSpyLinks.map((link) => {
            const section = document.getElementById(decodeURIComponent(link.hash.slice(1)));
            return section ? [section.id, section] : null;
        }).filter(Boolean)).values()];

        const updateActiveNavigation = () => {
            if (!scrollSpySections.length) return;

            const headerBottom = document.querySelector('header.sticky')?.getBoundingClientRect().bottom ?? 0;
            const activationLine = headerBottom + 32;
            let activeSection = scrollSpySections[0];

            for (const section of scrollSpySections) {
                if (section.getBoundingClientRect().top <= activationLine) {
                    activeSection = section;
                } else {
                    break;
                }
            }

            const atPageEnd = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
            if (atPageEnd) activeSection = scrollSpySections.at(-1);

            for (const link of scrollSpyLinks) {
                const isActive = decodeURIComponent(link.hash.slice(1)) === activeSection.id;
                if (isActive) {
                    link.setAttribute('aria-current', 'location');
                } else {
                    link.removeAttribute('aria-current');
                }
            }
        };

        let scrollSpyFrame = null;
        const scheduleActiveNavigationUpdate = () => {
            if (scrollSpyFrame !== null) return;
            scrollSpyFrame = requestAnimationFrame(() => {
                updateActiveNavigation();
                scrollSpyFrame = null;
            });
        };

        window.addEventListener('scroll', scheduleActiveNavigationUpdate, { passive: true });
        window.addEventListener('resize', scheduleActiveNavigationUpdate);
        window.addEventListener('hashchange', scheduleActiveNavigationUpdate);
        updateActiveNavigation();

        // Desplazamiento hacia las secciones del menú con el desplazamiento suave
        // NATIVO del navegador (no un bucle en el hilo principal). Si la persona
        // pidió reducir el movimiento en su sistema, el salto es directo.
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

        document.addEventListener('click', (event) => {
            const clicked = event.target instanceof Element ? event.target : event.target.parentElement;
            const link = clicked?.closest('a[href^="#"]');
            if (!link) return;

            const target = document.getElementById(decodeURIComponent(link.hash.slice(1)));
            if (!target) return;

            event.preventDefault();
            const header = document.querySelector('header.sticky');
            const offset = (header?.getBoundingClientRect().height ?? 0) + 12;
            const destination = Math.max(0, target.getBoundingClientRect().top + window.scrollY - offset);

            window.scrollTo({ top: destination, behavior: prefersReducedMotion.matches ? 'auto' : 'smooth' });
            history.pushState(null, '', link.hash);
        });
    </script>
</body>
</html>
