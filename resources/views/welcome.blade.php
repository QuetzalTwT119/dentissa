<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dentissa — Odontología Integral | Dra. Melissa López N.</title>

    <!-- Tipografía Editorial Boutique Local -->
    <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/figtree-latin-600-normal.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Tailwind CSS y Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            brand: {
                                primary: '#d75078',
                                'primary-hover': '#bd3f64',
                                secondary: '#f2b0a6',
                                bg: '#FAF6F6',
                                dark: '#2D2526',
                                muted: '#8A7A7D',
                                success: '#4E8D72',
                            }
                        },
                        fontFamily: {
                            sans: ['Figtree', 'sans-serif'],
                            serif: ['Georgia', 'serif'],
                        }
                    }
                }
            }
        </script>
    @endif

    <style>
        body {
            font-family: 'Figtree', system-ui, -apple-system, sans-serif;
            background-color: #FAF6F6;
            color: #2D2526;
        }
        .font-serif-luxury {
            font-family: Georgia, serif;
        }
    </style>
</head>
<body class="antialiased selection:bg-[#f2b0a6] selection:text-[#2D2526] bg-[#FAF6F6] text-[#2D2526]">
    <x-asistente-chat />

    <!-- Barra de Anuncio Superior -->
    <div class="bg-gradient-to-r from-[#d75078] via-[#bd3f64] to-[#a12a54] text-white text-xs py-2 px-4 text-center font-medium tracking-wide flex items-center justify-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full bg-[#f2b0a6] animate-pulse"></span>
        <span>Atención odontológica personalizada &bull; Consulta con previa cita</span>
        <span class="hidden md:inline text-white/60">|</span>
        <a href="#contacto" class="hidden md:inline font-semibold underline underline-offset-2 hover:text-[#f2b0a6] transition-colors">Horarios de consulta</a>
    </div>

    <!-- Navegación Principal de Escritorio & Móvil -->
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-[#e9dfdb]/80 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="h-20 flex items-center justify-between gap-4" aria-label="Navegación principal">
                
                <!-- Logo Oficial -->
                <a href="{{ url('/') }}" class="flex items-center gap-3 shrink-0 group">
                    <img src="{{ asset('images/9.jpg') }}" alt="Logo Dentissa" class="w-11 h-11 object-contain rounded-full border border-[#f2b0a6]/50 p-0.5 bg-white shadow-xs group-hover:scale-105 transition-transform">
                    <div class="leading-tight">
                        <span class="font-serif-luxury font-bold text-lg sm:text-xl tracking-tight text-[#2D2526] block">Dra. Melissa López</span>
                        <span class="text-[11px] font-semibold tracking-wider uppercase text-brand-600 block">Odontología Integral</span>
                    </div>
                </a>

                <!-- Menú de Escritorio (Visible en pantallas grandes >= 1024px) -->
                <div class="hidden lg:flex items-center gap-6 xl:gap-8 text-sm font-medium text-[#2D2526]/80">
                    <a href="{{ url('/') }}" class="text-[#d75078] font-bold hover:text-[#d75078] transition-colors">Inicio</a>
                    <a href="#servicios" class="hover:text-[#d75078] transition-colors">Tratamientos</a>
                    <a href="#nosotros" class="hover:text-[#d75078] transition-colors">La Clínica</a>
                    <a href="#galeria" class="hover:text-[#d75078] transition-colors">Instalaciones</a>
                    <a href="#testimonios" class="hover:text-[#d75078] transition-colors">Testimonios</a>
                    <a href="#contacto" class="hover:text-[#d75078] transition-colors">Contacto</a>
                </div>

                <!-- Acciones de Cabecera (Desktop & Toggle Móvil) -->
                <div class="flex items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="hidden sm:inline-flex text-xs font-bold text-[#2D2526] hover:text-[#d75078] px-3.5 py-2 rounded-xl transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="hidden sm:inline-flex text-xs font-bold text-[#2D2526] hover:text-[#d75078] px-3.5 py-2 rounded-xl transition-colors">
                                Portal Paciente
                            </a>
                        @endauth
                    @else
                        <a href="{{ url('/login') }}" class="hidden sm:inline-flex text-xs font-bold text-[#2D2526] hover:text-[#d75078] px-3.5 py-2 rounded-xl transition-colors">
                            Portal Paciente
                        </a>
                    @endif

                    <a href="{{ route('citas.publica.create') }}" class="hidden sm:inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#d75078] to-[#bd3f64] px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-soft transition-all duration-200 hover:from-[#bd3f64] hover:to-[#a12a54] hover:shadow-md hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Agendar Cita</span>
                    </a>

                    <!-- Botón Hamburguesa (Únicamente en Móvil/Tablet < 1024px) -->
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            :aria-expanded="mobileMenuOpen.toString()"
                            :aria-label="mobileMenuOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'"
                            aria-controls="mobile-navigation-menu"
                            class="inline-flex lg:hidden items-center justify-center p-2 rounded-xl text-[#2D2526] hover:text-[#d75078] hover:bg-[#FAF6F6] focus:outline-none focus:ring-2 focus:ring-[#d75078]/40 border border-[#e9dfdb] transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path :class="{'hidden': mobileMenuOpen, 'inline-flex': !mobileMenuOpen}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': !mobileMenuOpen, 'inline-flex': mobileMenuOpen}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </nav>
        </div>

        <!-- Menú Móvil Desplegable -->
        <div id="mobile-navigation-menu"
             x-cloak
             x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.outside="mobileMenuOpen = false"
             @keydown.escape.window="mobileMenuOpen = false"
             class="border-t border-[#e9dfdb] bg-white px-4 py-5 shadow-xl lg:hidden">
            
            <nav class="flex flex-col gap-1 text-sm font-semibold text-[#2D2526]" aria-label="Menú móvil">
                <a href="{{ url('/') }}" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#d75078] bg-brand-50/60 transition-colors">
                    <span>Inicio</span>
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#servicios" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#403539] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                    <span>Tratamientos</span>
                    <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#nosotros" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#403539] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                    <span>La Clínica</span>
                    <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#galeria" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#403539] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                    <span>Instalaciones</span>
                    <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#testimonios" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#403539] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                    <span>Testimonios</span>
                    <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                <a href="#contacto" @click="mobileMenuOpen = false" class="flex items-center justify-between rounded-xl px-4 py-3 text-[#403539] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                    <span>Contacto</span>
                    <svg class="h-4 w-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </nav>

            <div class="mt-4 pt-4 border-t border-[#e9dfdb] flex flex-col gap-2.5">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center rounded-xl bg-[#FAF6F6] px-4 py-3 text-xs font-bold text-[#2D2526] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center rounded-xl bg-[#FAF6F6] px-4 py-3 text-xs font-bold text-[#2D2526] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                            Portal Paciente
                        </a>
                    @endauth
                @else
                    <a href="{{ url('/login') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center rounded-xl bg-[#FAF6F6] px-4 py-3 text-xs font-bold text-[#2D2526] hover:bg-brand-50 hover:text-[#d75078] transition-colors">
                        Portal Paciente
                    </a>
                @endif

                <a href="{{ route('citas.publica.create') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#d75078] to-[#bd3f64] px-5 py-3.5 text-xs font-bold text-white shadow-soft transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Agendar Cita</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section Editorial & Equilibrado -->
    <section id="inicio" class="relative py-12 lg:py-20 overflow-hidden bg-gradient-to-b from-white via-[#FAF6F6] to-[#FAF6F6]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

                <!-- Columna Izquierda: Información de la Clínica -->
                <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-brand-200 shadow-2xs text-xs font-bold text-brand-700">
                        <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                        <span>Espacio de alta estética dental y bienestar</span>
                    </div>

                    <h1 class="font-serif-luxury text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-[#2D2526] leading-[1.2]">
                        Tu sonrisa merece una experiencia
                        <span class="text-[#d75078] italic font-semibold">
                            cálida, delicada
                        </span>
                        y sin dolor.
                    </h1>

                    <p class="text-base sm:text-lg text-[#6b5e62] max-w-xl mx-auto lg:mx-0 font-normal leading-relaxed">
                        Combinamos tecnología de precisión y un trato profundamente humano para que recuperar tu salud y estética dental sea un momento de bienestar.
                    </p>

                    <!-- Acciones del Hero -->
                    <div data-asistente-zona-reservada class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                        <a href="{{ route('citas.publica.create') }}" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-[#d75078] hover:bg-[#bd3f64] text-white font-bold text-sm shadow-soft hover:shadow-md hover:-translate-y-0.5 transition-all text-center flex items-center justify-center gap-2 group">
                            <span>Reservar mi valoración</span>
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>

                        <a href="{{ route('citas.publica.create') }}" class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white hover:bg-[#FAF6F6] text-[#2D2526] border border-[#e9dfdb] font-bold text-sm transition-all text-center shadow-xs hover:border-brand-300">
                            Elegir fecha y horario
                        </a>
                    </div>

                    <!-- Pilares de Confianza -->
                    <div class="pt-6 grid grid-cols-3 gap-4 border-t border-[#e9dfdb] max-w-lg mx-auto lg:mx-0 text-left">
                        <div>
                            <span class="block font-serif-luxury text-xl sm:text-2xl font-bold text-[#d75078]">100%</span>
                            <span class="text-xs text-[#8A7A7D] font-medium leading-tight mt-0.5 block">Personalizado</span>
                        </div>
                        <div>
                            <span class="block font-serif-luxury text-xl sm:text-2xl font-bold text-[#4E8D72]">Digital</span>
                            <span class="text-xs text-[#8A7A7D] font-medium leading-tight mt-0.5 block">Expediente seguro</span>
                        </div>
                        <div>
                            <span class="block font-serif-luxury text-xl sm:text-2xl font-bold text-[#d75078]">Humana</span>
                            <span class="text-xs text-[#8A7A7D] font-medium leading-tight mt-0.5 block">Atención cercana</span>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Imagen Oficial 5.jpg Proporcionada -->
                <div class="lg:col-span-5">
                    <div class="relative mx-auto max-w-md">
                        <div class="overflow-hidden rounded-3xl bg-white p-2.5 shadow-xl border border-[#e9dfdb]">
                            <div class="relative rounded-2xl overflow-hidden aspect-[4/5]">
                                <img src="{{ asset('images/5.jpg') }}" alt="Sonrisa Radiante Dentissa" class="w-full h-full object-cover" fetchpriority="high">

                                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent flex flex-col justify-between p-5">
                                    <div class="flex items-center justify-between">
                                        <span class="bg-white/95 backdrop-blur-md text-[#d75078] text-xs font-bold px-3 py-1 rounded-full shadow-xs flex items-center gap-1.5">
                                            <img src="{{ asset('images/9.jpg') }}" class="w-4 h-4 rounded-full" alt="logo">
                                            Dra. Melissa López N.
                                        </span>
                                        <span class="bg-[#4E8D72] text-white text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1.5 shadow-xs">
                                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                            Agenda en línea
                                        </span>
                                    </div>

                                    <div class="bg-white/95 backdrop-blur-md p-4 rounded-2xl border border-white/80 shadow-md">
                                        <p class="text-xs font-bold text-[#2D2526]">Estética & Salud Dental Integral</p>
                                        <p class="text-[11px] text-[#6b5e62] mt-0.5">Diagnóstico claro y tratamientos conservadores desde tu primera consulta.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Beneficios Clínicos -->
    <section class="py-12 bg-white border-y border-[#e9dfdb]/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-[#FAF6F6]/60 border border-[#e9dfdb]/70">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center shrink-0 text-[#d75078] border border-brand-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-serif-luxury text-base font-bold text-[#2D2526]">Atención Humana y Sin Prisas</h3>
                        <p class="text-xs text-[#6b5e62] mt-1 leading-relaxed">
                            Cada consulta está programada con el tiempo exacto para escucharte, resolver dudas y atenderte con calma.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-[#FAF6F6]/60 border border-[#e9dfdb]/70">
                    <div class="w-12 h-12 rounded-2xl bg-brand-50 flex items-center justify-center shrink-0 text-[#d75078] border border-brand-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-serif-luxury text-base font-bold text-[#2D2526]">Instrumental Estéril & Preciso</h3>
                        <p class="text-xs text-[#6b5e62] mt-1 leading-relaxed">
                            Bioseguridad de nivel clínico con instrumental empaquetado y tecnología para procedimientos cómodos.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl bg-[#FAF6F6]/60 border border-[#e9dfdb]/70">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center shrink-0 text-[#4E8D72] border border-emerald-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-serif-luxury text-base font-bold text-[#2D2526]">Diagnóstico Transparente</h3>
                        <p class="text-xs text-[#6b5e62] mt-1 leading-relaxed">
                            Explicación clara con tu expediente clínico digital, sin tratamientos innecesarios ni costos ocultos.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Catálogo de Tratamientos -->
    <section id="servicios" class="py-16 lg:py-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <span class="text-xs font-bold tracking-widest text-[#d75078] uppercase">Cuidado Especializado</span>
            <h2 class="font-serif-luxury text-3xl sm:text-4xl font-bold text-[#2D2526] mt-2">
                Tratamientos diseñados para tu salud y armonía
            </h2>
            <p class="text-sm text-[#6b5e62] mt-2.5">
                Sesiones programadas con tiempo y dedicación para brindarte una atención puntual, cómoda y sin prisas.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 01 Valoración -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#e9dfdb] shadow-soft relative flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-[#FAF6F6] text-[#d75078] flex items-center justify-center font-serif-luxury font-bold text-lg border border-[#e9dfdb]">01</span>
                        <span class="text-[11px] font-bold text-[#8A7A7D] bg-[#FAF6F6] px-3 py-1 rounded-full border border-[#e9dfdb] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#d75078]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            30 min
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl font-bold text-[#2D2526]">Valoración Integral</h3>
                    <p class="text-xs text-[#6b5e62] mt-2 leading-relaxed">
                        Revisión clínica completa, exploración intraoral y diagnóstico personalizado con un enfoque preventivo y cuidadoso.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-[#e9dfdb]/70 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-[#8A7A7D]">Enfoque</span>
                        <span class="text-xs font-bold text-[#2D2526] block">Diagnóstico claro</span>
                    </div>
                    <a href="{{ route('citas.publica.create') }}" class="text-xs font-bold text-[#d75078] hover:text-[#bd3f64] flex items-center gap-1 group">
                        Agendar <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>

            <!-- 02 Limpieza -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#e9dfdb] shadow-soft relative flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-[#FAF6F6] text-[#d75078] flex items-center justify-center font-serif-luxury font-bold text-lg border border-[#e9dfdb]">02</span>
                        <span class="text-[11px] font-bold text-[#8A7A7D] bg-[#FAF6F6] px-3 py-1 rounded-full border border-[#e9dfdb] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#d75078]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            1 hora
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl font-bold text-[#2D2526]">Limpieza Dental Profunda</h3>
                    <p class="text-xs text-[#6b5e62] mt-2 leading-relaxed">
                        Eliminación de sarro y placa mediante ultrasonido suave, profilaxis y pulido para encías sanas y aliento fresco.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-[#e9dfdb]/70 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-[#8A7A7D]">Frecuencia</span>
                        <span class="text-xs font-bold text-[#2D2526] block">Cada 6 meses</span>
                    </div>
                    <a href="{{ route('citas.publica.create') }}" class="text-xs font-bold text-[#d75078] hover:text-[#bd3f64] flex items-center gap-1 group">
                        Agendar <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>

            <!-- 03 Blanqueamiento Destacado -->
            <div class="bg-gradient-to-br from-white to-[#FAF6F6] rounded-3xl p-6 sm:p-7 border-2 border-brand-300 shadow-soft relative flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <span class="absolute -top-3 right-6 bg-[#d75078] text-white text-[10px] font-bold uppercase tracking-wider px-3 py-0.5 rounded-full shadow-xs">
                    Más solicitado
                </span>
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-[#d75078] text-white flex items-center justify-center font-serif-luxury font-bold text-lg shadow-xs">03</span>
                        <span class="text-[11px] font-bold text-[#d75078] bg-brand-50 px-3 py-1 rounded-full border border-brand-200 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#d75078]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            1.5 horas
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl font-bold text-[#2D2526]">Información por confirmar</h3>
                    <p class="text-xs text-[#6b5e62] mt-2 leading-relaxed">
                        Tecnología aclarante de alta eficacia para devolver la luminosidad natural a tus dientes sin desgastar el esmalte.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-[#e9dfdb]/70 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-[#8A7A7D]">Resultados</span>
                        <span class="text-xs font-bold text-[#d75078] block">Desde 1 sesión</span>
                    </div>
                    <a href="{{ route('citas.publica.create') }}" class="text-xs font-bold text-[#d75078] hover:text-[#bd3f64] flex items-center gap-1 group">
                        Agendar <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>

            <!-- 04 Resinas -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#e9dfdb] shadow-soft relative flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-[#FAF6F6] text-[#d75078] flex items-center justify-center font-serif-luxury font-bold text-lg border border-[#e9dfdb]">04</span>
                        <span class="text-[11px] font-bold text-[#8A7A7D] bg-[#FAF6F6] px-3 py-1 rounded-full border border-[#e9dfdb] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#d75078]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            1 hora
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl font-bold text-[#2D2526]">Retiro de Caries & Resinas</h3>
                    <p class="text-xs text-[#6b5e62] mt-2 leading-relaxed">
                        Restauraciones estéticas y biocompatibles del color exacto de tu diente, devolviendo forma y funcionalidad.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-[#e9dfdb]/70 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-[#8A7A7D]">Materiales</span>
                        <span class="text-xs font-bold text-[#2D2526] block">Resina estética</span>
                    </div>
                    <a href="{{ route('citas.publica.create') }}" class="text-xs font-bold text-[#d75078] hover:text-[#bd3f64] flex items-center gap-1 group">
                        Agendar <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>

            <!-- 05 Extracción -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#e9dfdb] shadow-soft relative flex flex-col justify-between hover:-translate-y-1 transition-all duration-300">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="w-10 h-10 rounded-2xl bg-[#FAF6F6] text-[#d75078] flex items-center justify-center font-serif-luxury font-bold text-lg border border-[#e9dfdb]">05</span>
                        <span class="text-[11px] font-bold text-[#8A7A7D] bg-[#FAF6F6] px-3 py-1 rounded-full border border-[#e9dfdb] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#d75078]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Hasta 2 horas
                        </span>
                    </div>
                    <h3 class="font-serif-luxury text-xl font-bold text-[#2D2526]">Extracción Cuidadosa</h3>
                    <p class="text-xs text-[#6b5e62] mt-2 leading-relaxed">
                        Procedimiento mínimamente invasivo con anestesia precisa para una recuperación rápida, cómoda y segura.
                    </p>
                </div>
                <div class="pt-5 mt-5 border-t border-[#e9dfdb]/70 flex items-center justify-between">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-[#8A7A7D]">Enfoque</span>
                        <span class="text-xs font-bold text-[#2D2526] block">Sin dolor</span>
                    </div>
                    <a href="{{ route('citas.publica.create') }}" class="text-xs font-bold text-[#d75078] hover:text-[#bd3f64] flex items-center gap-1 group">
                        Agendar <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </a>
                </div>
            </div>

            <!-- 06 Instrumental Real 3.jpg -->
            <div class="relative rounded-3xl overflow-hidden shadow-soft border border-[#e9dfdb] group flex flex-col justify-end p-6 sm:p-7 min-h-[300px]">
                <img src="{{ asset('images/3.jpg') }}" alt="Instrumental Dental de Precisión" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent"></div>

                <div class="relative z-10 text-white">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#f2b0a6] bg-black/40 px-2.5 py-1 rounded-full border border-white/20">Bioseguridad de Grado Clínico</span>
                    <h3 class="font-serif-luxury text-xl font-bold text-white mt-2">Instrumental Estéril Garantizado</h3>
                    <p class="text-xs text-white/80 mt-1 leading-relaxed">
                        Cada set de instrumental es sellado y esterilizado individualmente para tu máxima protección.
                    </p>
                    <a href="{{ route('citas.publica.create') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#f2b0a6] hover:text-white mt-3 transition-colors">
                        <span>Agendar consulta</span> <span>→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- La Clínica y Doctora Titular con Foto Oficial 4.jpg -->
    <section id="nosotros" class="py-16 lg:py-24 bg-white border-y border-[#e9dfdb]/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">

                <!-- Foto Oficial 4.jpg -->
                <div class="lg:col-span-5">
                    <div class="relative mx-auto max-w-md">
                        <div class="rounded-3xl bg-[#FAF6F6] p-3 border border-[#e9dfdb] shadow-soft">
                            <div class="rounded-2xl overflow-hidden aspect-[4/5] relative group">
                                <img src="{{ asset('images/4.jpg') }}" alt="Uniforme Dra. Melissa López N." class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">

                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent flex flex-col justify-end p-6 text-white">
                                    <span class="text-[10px] tracking-widest uppercase font-bold text-[#f2b0a6]">Directora Médica</span>
                                    <h4 class="font-serif-luxury text-2xl font-bold">Dra. Melissa López N.</h4>
                                    <p class="text-xs text-white/80 mt-0.5">Odontología Integral & Armonización</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Respaldo Clínico -->
                <div class="lg:col-span-7 space-y-6 text-left">
                    <div>
                        <span class="text-xs font-bold tracking-widest text-[#d75078] uppercase">Nuestra Filosofía</span>
                        <h2 class="font-serif-luxury text-3xl sm:text-4xl font-bold text-[#2D2526] mt-2 leading-snug">
                            Cuidado dental personalizado en un ambiente de absoluta serenidad
                        </h2>
                    </div>
                    
                    <p class="text-sm text-[#6b5e62] leading-relaxed">
                        En Dentissa rediseñamos la consulta dental para que olvides el estrés: iluminación cálida, instrumental suave y, sobre todo, una comunicación honesta y pausada en cada paso.
                    </p>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-start gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-[#4E8D72]/20 text-[#4E8D72] flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-xs text-[#2D2526]"><strong class="font-bold">Historial clínico digital:</strong> Información gestionada de forma confidencial dentro del sistema de la clínica.</p>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-[#4E8D72]/20 text-[#4E8D72] flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-xs text-[#2D2526]"><strong class="font-bold">Bioseguridad y esterilización de grado clínico:</strong> Instrumental empacado y esterilizado individualmente, abierto siempre ante tu presencia.</p>
                        </div>

                        <div class="flex items-start gap-3.5">
                            <div class="w-6 h-6 rounded-full bg-[#4E8D72]/20 text-[#4E8D72] flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <p class="text-xs text-[#2D2526]"><strong class="font-bold">Puntualidad en tu agenda:</strong> Bloqueo dinámico de horarios para evitar esperas innecesarias en sala de recepción.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Galería con Fotos Oficiales 6.jpg y 2.jpg -->
    <section id="galeria" class="py-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="text-xs font-bold tracking-widest text-[#d75078] uppercase">Ambiente Boutique</span>
            <h2 class="font-serif-luxury text-3xl font-bold text-[#2D2526] mt-2">
                Cada detalle pensado para que te sientas en casa
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded-3xl overflow-hidden shadow-soft border border-[#e9dfdb] aspect-[16/10] relative group">
                <img src="{{ asset('images/6.jpg') }}" alt="Detalles del consultorio" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent flex items-end p-6 text-white">
                    <div>
                        <h4 class="font-serif-luxury text-xl font-bold">Calidez y Hospitalidad</h4>
                        <p class="text-xs text-white/80 mt-1">Disfruta de una sala relajante y una atención pensada para tu total tranquilidad.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl overflow-hidden shadow-soft border border-[#e9dfdb] aspect-[16/10] relative group">
                <img src="{{ asset('images/2.jpg') }}" alt="Identidad de marca Dentissa" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-transparent to-transparent flex items-end p-6 text-white">
                    <div>
                        <h4 class="font-serif-luxury text-xl font-bold">Paleta de Marca Relajante</h4>
                        <p class="text-xs text-white/80 mt-1">Colores rosa y durazno cálidos que transmiten serenidad, confianza y bienestar.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonios de Pacientes -->
    <section id="testimonios" class="py-16 lg:py-20 bg-white border-y border-[#e9dfdb]/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs font-bold tracking-widest text-[#d75078] uppercase">Experiencias de pacientes</span>
                <h2 class="font-serif-luxury text-3xl font-bold text-[#2D2526] mt-2">
                    Sonrisas que hablan por nosotros
                </h2>
                <p class="text-xs text-[#8A7A7D] mt-2">
                    Las opiniones se publican únicamente cuando cuentan con autorización de la clínica y de la persona titular.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-[#FAF6F6] rounded-3xl p-6 border border-[#e9dfdb] shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex text-amber-400 text-xs mb-3">★★★★★</div>
                        <p class="text-xs text-[#2D2526] leading-relaxed italic">
                            “Testimonio pendiente de autorización para publicación.”
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-[#e9dfdb]/70 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-[#d75078] font-bold text-xs flex items-center justify-center">
                            MC
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-[#2D2526]">Testimonio pendiente</h5>
                            <span class="text-[11px] text-[#8A7A7D]">Información por confirmar</span>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FAF6F6] rounded-3xl p-6 border border-[#e9dfdb] shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex text-amber-400 text-xs mb-3">★★★★★</div>
                        <p class="text-xs text-[#2D2526] leading-relaxed italic">
                            “Testimonio pendiente de autorización para publicación.”
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-[#e9dfdb]/70 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-[#d75078] font-bold text-xs flex items-center justify-center">
                            RG
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-[#2D2526]">Testimonio pendiente</h5>
                            <span class="text-[11px] text-[#8A7A7D]">Blanqueamiento Dental</span>
                        </div>
                    </div>
                </div>

                <div class="bg-[#FAF6F6] rounded-3xl p-6 border border-[#e9dfdb] shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex text-amber-400 text-xs mb-3">★★★★★</div>
                        <p class="text-xs text-[#2D2526] leading-relaxed italic">
                            “Testimonio pendiente de autorización para publicación.”
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-[#e9dfdb]/70 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-[#d75078] font-bold text-xs flex items-center justify-center">
                            SV
                        </div>
                        <div>
                            <h5 class="text-xs font-bold text-[#2D2526]">Testimonio pendiente</h5>
                            <span class="text-[11px] text-[#8A7A7D]">Información por confirmar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Módulo: Llamada a Agendar Cita -->
    <section id="agendar" class="py-16 lg:py-20 bg-gradient-to-b from-[#FAF6F6] to-white relative">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-3xl p-8 sm:p-10 border border-[#e9dfdb] shadow-xl text-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-brand-50 text-[#d75078] text-xs font-bold border border-brand-200">
                    ✦ Reserva Fácil & Rápida
                </span>
                <h2 class="font-serif-luxury text-3xl sm:text-4xl font-bold text-[#2D2526] mt-3">
                    Comienza tu camino hacia una mejor sonrisa
                </h2>
                <p class="text-xs sm:text-sm text-[#6b5e62] mt-2 max-w-lg mx-auto">
                    Elige el tratamiento, la fecha y el horario que mejor se adapten a tu día para tu valoración en Dentissa.
                </p>

                <div class="mt-8 flex justify-center">
                    <a href="{{ route('citas.publica.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#d75078] to-[#bd3f64] px-8 py-4 text-sm font-bold text-white shadow-soft transition-all duration-200 hover:from-[#bd3f64] hover:to-[#a12a54] hover:shadow-md hover:-translate-y-0.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Elegir fecha y horario</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Elegante -->
    <footer id="contacto" class="bg-[#2D2526] text-white pt-14 pb-10 border-t border-[#8A7A7D]/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-white/10">

                <div class="md:col-span-1 space-y-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/9.jpg') }}" alt="Logo Dentissa" class="w-10 h-10 object-contain rounded-full bg-white p-0.5">
                        <div>
                            <span class="font-serif-luxury font-bold text-lg text-white block leading-none">Dentissa</span>
                            <span class="text-[10px] text-[#f2b0a6] tracking-wider uppercase font-semibold">Dra. Melissa López</span>
                        </div>
                    </div>
                    <p class="text-xs text-white/70 leading-relaxed">
                        Atención odontológica integral, estética y preventiva en un ambiente cálido y seguro.
                    </p>
                </div>

                <div>
                    <h4 class="font-serif-luxury font-bold text-xs text-[#f2b0a6] uppercase tracking-wider mb-3">Tratamientos</h4>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li><a href="#servicios" class="hover:text-white transition-colors">Valoración Integral</a></li>
                        <li><a href="#servicios" class="hover:text-white transition-colors">Limpieza Profiláctica</a></li>
                        <li><a href="#servicios" class="hover:text-white transition-colors">Resinas Biocompatibles</a></li>
                        <li><a href="#servicios" class="hover:text-white transition-colors">Extracciones Suaves</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-serif-luxury font-bold text-xs text-[#f2b0a6] uppercase tracking-wider mb-3">Horarios</h4>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li>Horarios por confirmar</li>
                        <li class="pt-1 text-[#f2b0a6] font-semibold">Atención con previa cita</li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-serif-luxury font-bold text-xs text-[#f2b0a6] uppercase tracking-wider mb-3">Acceso al Sistema</h4>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li>Portal privado para personal y pacientes.</li>
                        <li class="pt-2">
                            <a href="{{ url('/login') }}" class="inline-block px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-white transition-all">
                                Iniciar sesión →
                            </a>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-white/40 gap-3">
                <p>© {{ date('Y') }} Dentissa &bull; Dra. Melissa López N. Todos los derechos reservados.</p>
                <p>Información clínica y de contacto sujeta a validación de la clínica.</p>
            </div>
        </div>
    </footer>

</body>
</html>
