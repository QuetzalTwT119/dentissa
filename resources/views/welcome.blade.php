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

    <!-- Activa las animaciones de entrada solo si hay JavaScript -->
    <script>document.documentElement.classList.add('js')</script>

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

        $testimoniosPendientes = [
            ['MC', 'Información por confirmar'],
            ['RG', 'Blanqueamiento Dental'],
            ['SV', 'Información por confirmar'],
        ];
    @endphp

    <!-- ============ Navegación ============ -->
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 pt-[env(safe-area-inset-top)] border-b border-line/70 bg-cream/90 backdrop-blur-md supports-[backdrop-filter]:bg-cream/75">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav class="flex h-[4.5rem] items-center justify-between gap-4" aria-label="Navegación principal">

                <!-- Logo con firma cursiva -->
                <a href="{{ url('/') }}" class="flex min-w-0 shrink-0 items-center gap-3 rounded-full">
                    <img src="{{ asset('images/9.jpg') }}" alt="Logo Dentissa" class="h-11 w-11 shrink-0 rounded-full bg-white object-contain p-0.5 ring-1 ring-peach-200" width="44" height="44">
                    <span class="min-w-0 leading-none">
                        <span class="block truncate firma text-[1.2rem] leading-none text-brand-700 sm:text-[1.35rem]">Dra. Melissa López</span>
                        <span class="mt-1 block text-caption font-semibold uppercase tracking-label text-muted">Odontología Integral</span>
                    </span>
                </a>

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
        <section id="inicio" class="relative overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute -right-40 -top-24 h-[34rem] w-[34rem] rounded-full bg-peach-200/50 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -left-40 bottom-0 h-80 w-80 rounded-full bg-brand-100/60 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 pb-14 pt-10 sm:px-6 sm:pt-14 lg:grid-cols-12 lg:gap-14 lg:px-8 lg:pb-24 lg:pt-20">

                <div class="lg:col-span-6">
                    <p class="eyebrow">Espacio de alta estética dental y bienestar</p>

                    <h1 class="mt-5 text-display text-ink">
                        Tu sonrisa merece una experiencia
                        <em class="font-medium italic text-brand-600">cálida, delicada</em>
                        y sin dolor.
                    </h1>

                    <p class="mt-6 max-w-xl text-body text-ink-soft sm:text-lead">
                        Combinamos tecnología de precisión y un trato profundamente humano para que recuperar tu salud y estética dental sea un momento de bienestar.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('citas.publica.create') }}" class="btn-brand group px-7 py-3.5 text-callout">
                            <span>Reservar mi valoración</span>
                            <span class="transition-transform duration-200 ease-out group-hover:translate-x-0.5">{!! $iconoFlecha !!}</span>
                        </a>
                        <a href="{{ route('citas.publica.create') }}" class="btn-secondary px-7 py-3.5 text-callout">
                            Elegir fecha y horario
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-line pt-6">
                        <div>
                            <dt class="sr-only">Atención</dt>
                            <dd class="font-serif text-title-3 text-brand-600 sm:text-title-2">100%</dd>
                            <dd class="mt-1 text-xs leading-tight text-muted">Personalizado</dd>
                        </div>
                        <div>
                            <dt class="sr-only">Expediente</dt>
                            <dd class="font-serif text-title-3 text-sage-600 sm:text-title-2">Digital</dd>
                            <dd class="mt-1 text-xs leading-tight text-muted">Expediente seguro</dd>
                        </div>
                        <div>
                            <dt class="sr-only">Trato</dt>
                            <dd class="font-serif text-title-3 text-brand-600 sm:text-title-2">Humana</dd>
                            <dd class="mt-1 text-xs leading-tight text-muted">Atención cercana</dd>
                        </div>
                    </dl>
                </div>

                <!-- Fotografía principal -->
                <div class="lg:col-span-6">
                    <figure class="relative mx-auto max-w-md lg:max-w-none">
                        <div class="relative aspect-[4/5] overflow-hidden rounded-[2.25rem] bg-shell shadow-lift sm:aspect-[5/6] lg:rounded-[2.75rem]">
                            <img src="{{ asset('images/5.jpg') }}" alt="Sonrisa Radiante Dentissa" class="h-full w-full object-cover" fetchpriority="high">
                            <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-t from-[#2b1a20]/55 via-transparent to-transparent"></div>

                            <span class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-full bg-white/95 py-1 pl-1 pr-3 text-xs font-semibold text-brand-700 shadow-xs backdrop-blur sm:left-5 sm:top-5">
                                <img src="{{ asset('images/9.jpg') }}" class="h-6 w-6 rounded-full" alt="">
                                Dra. Melissa López N.
                            </span>
                        </div>

                        <figcaption class="absolute -bottom-6 left-4 right-4 rounded-3xl border border-white/70 bg-white/95 p-4 shadow-lift backdrop-blur sm:left-6 sm:right-auto sm:max-w-xs lg:-left-8">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-sage-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-sage-500" aria-hidden="true"></span>
                                Agenda en línea
                            </span>
                            <p class="mt-1.5 font-serif text-title-3 text-ink">Estética & Salud Dental Integral</p>
                            <p class="mt-1 text-footnote text-muted">Diagnóstico claro y tratamientos conservadores desde tu primera consulta.</p>
                        </figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <!-- ============ Beneficios ============ -->
        <section aria-label="Beneficios" class="border-y border-line/80 bg-white">
            <div class="mx-auto grid max-w-7xl gap-px overflow-hidden px-4 sm:px-6 md:grid-cols-3 lg:px-8">
                @foreach ($beneficios as $beneficio)
                    <div data-reveal class="flex items-start gap-4 py-7 md:px-6 md:py-10 md:first:pl-0 md:[&:not(:first-child)]:border-l md:[&:not(:first-child)]:border-line/80 [&:not(:first-child)]:border-t [&:not(:first-child)]:border-line/80 md:[&:not(:first-child)]:border-t-0" style="transition-delay: {{ $loop->index * 60 }}ms">
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
            <div data-reveal class="max-w-2xl">
                <p class="eyebrow">Cuidado Especializado</p>
                <h2 class="mt-4 text-title-1 text-ink">
                    Tratamientos diseñados para tu salud y armonía
                </h2>
                <p class="mt-4 text-body text-muted">
                    Sesiones programadas con tiempo y dedicación para brindarte una atención puntual, cómoda y sin prisas.
                </p>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($tratamientos as [$numero, $titulo, $texto, $duracion, $etiqueta, $valor, $destacado])
                    <article data-reveal style="transition-delay: {{ ($loop->index % 3) * 60 }}ms" @class([
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
                <article data-reveal style="transition-delay: 120ms" class="group relative flex min-h-[320px] flex-col justify-end overflow-hidden rounded-[1.75rem] p-6 text-white shadow-soft sm:p-7">
                    <img src="{{ asset('images/3.jpg') }}" alt="Instrumental Dental de Precisión" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]" loading="lazy">
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
        </section>

        <!-- ============ La clínica ============ -->
        <section id="nosotros" class="border-y border-line/80 bg-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-12 lg:gap-16 lg:px-8 lg:py-28">

                <figure data-reveal class="relative mx-auto w-full max-w-md lg:col-span-5 lg:max-w-none">
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
                    <div data-reveal>
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
                            <li data-reveal style="transition-delay: {{ $loop->index * 60 }}ms" class="flex items-start gap-4 py-5">
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
            <div data-reveal class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
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
                    <figure data-reveal style="transition-delay: {{ $loop->index * 80 }}ms" class="group relative min-h-[300px] overflow-hidden rounded-[2rem] bg-shell shadow-soft sm:min-h-[380px] {{ $columnas }}">
                        <img src="{{ asset('images/'.$imagen) }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]" loading="lazy">
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

        <!-- ============ Testimonios ============ -->
        <section id="testimonios" class="border-y border-line/80 bg-peach-50/60">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="eyebrow justify-center">Experiencias de pacientes</p>
                    <h2 class="mt-4 text-title-1 text-ink">
                        Sonrisas que hablan por nosotros
                    </h2>
                    <p class="mt-4 text-callout text-muted">
                        Las opiniones se publican únicamente cuando cuentan con autorización de la clínica y de la persona titular.
                    </p>
                </div>

                <div class="mt-12 grid grid-cols-1 gap-5 md:grid-cols-3">
                    @foreach ($testimoniosPendientes as [$iniciales, $detalle])
                        <figure data-reveal style="transition-delay: {{ $loop->index * 60 }}ms" class="card-boutique flex h-full flex-col p-6 sm:p-7">
                            <svg class="h-7 w-7 text-peach-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.6 6C6.5 7.3 4.5 10 4.5 13.4V18h6v-6H7.6c.2-1.9 1.4-3.4 3.2-4.2L9.6 6Zm9 0c-3.1 1.3-5.1 4-5.1 7.4V18h6v-6h-2.9c.2-1.9 1.4-3.4 3.2-4.2L18.6 6Z"/></svg>
                            <div class="mt-3 flex text-sm tracking-label text-amber-400" aria-label="5 estrellas">★★★★★</div>
                            <blockquote class="mt-3 flex-1 font-serif text-title-3 italic text-ink">
                                “Testimonio pendiente de autorización para publicación.”
                            </blockquote>
                            <figcaption class="mt-6 flex items-center gap-3 border-t border-line/80 pt-5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-peach-100 to-brand-100 text-xs font-semibold text-brand-800" aria-hidden="true">{{ $iniciales }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-ink">Testimonio pendiente</span>
                                    <span class="block truncate text-xs text-muted">{{ $detalle }}</span>
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- ============ Llamada a agendar ============ -->
        <section id="agendar" class="px-4 py-20 sm:px-6 lg:px-8 lg:py-24">
            <div data-reveal class="relative mx-auto max-w-5xl overflow-hidden rounded-[2.5rem] bg-brand-700 px-6 py-14 text-center text-white shadow-lift sm:px-12 lg:py-20">
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
                        <li>Horarios por confirmar</li>
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

        // Desplazamiento suave hacia las secciones del menú.
        // Duración corta (≈0.5–0.9 s) para que se sienta ágil; sin animación
        // si la persona pidió reducir el movimiento en su sistema.
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
            const start = window.scrollY;
            const destination = Math.max(0, target.getBoundingClientRect().top + start - offset);
            const distance = destination - start;
            const animationDuration = Math.min(900, Math.max(500, Math.abs(distance) * 0.25));

            if (window.dentissaScrollFrame) {
                cancelAnimationFrame(window.dentissaScrollFrame);
            }

            if (Math.abs(distance) < 2 || prefersReducedMotion.matches) {
                window.scrollTo({ top: destination, behavior: 'auto' });
            } else {
                const animationStart = performance.now();
                const animateScroll = (now) => {
                    const progress = Math.min((now - animationStart) / animationDuration, 1);
                    // ease-in-out cúbica: acelera y frena con suavidad.
                    const easedProgress = progress < 0.5
                        ? 4 * progress * progress * progress
                        : 1 - Math.pow(-2 * progress + 2, 3) / 2;

                    window.scrollTo({ top: start + distance * easedProgress, behavior: 'auto' });

                    if (progress < 1) {
                        window.dentissaScrollFrame = requestAnimationFrame(animateScroll);
                    } else {
                        window.dentissaScrollFrame = null;
                    }
                };

                window.dentissaScrollFrame = requestAnimationFrame(animateScroll);
            }

            history.pushState(null, '', link.hash);
        });
    </script>
</body>
</html>
