<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Melissa López N. — Odontología Integral</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|alex-brush:400&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php($imagenesServicios = config('dentissa.imagenes.servicios', []))
        <div class="min-h-screen bg-brand-50">
            <header class="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N." class="h-12 w-12 object-contain">
                    <div class="leading-tight">
                        <p class="font-cursive text-2xl text-brand-600">Melissa López N.</p>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-brand-400">Odontología integral</p>
                    </div>
                </div>

                <nav class="flex flex-col items-start gap-2 text-sm font-medium text-gray-600 lg:flex-row lg:flex-wrap lg:items-center lg:gap-x-5 lg:gap-y-2">
                    <a href="#servicios" class="hover:text-brand-600">Servicios</a>
                    @if ($ofertas->isNotEmpty())
                        <a href="#ofertas" class="hover:text-brand-600">Ofertas</a>
                    @endif
                    @if ($casos->isNotEmpty())
                        <a href="#casos" class="hover:text-brand-600">Casos</a>
                    @endif
                    @if ($testimonios->isNotEmpty())
                        <a href="#testimonios" class="hover:text-brand-600">Testimonios</a>
                    @endif
                    @if ($galeria->isNotEmpty())
                        <a href="#galeria" class="hover:text-brand-600">Galería</a>
                    @endif
                    @if ($faqs->isNotEmpty())
                        <a href="#faq" class="hover:text-brand-600">Preguntas frecuentes</a>
                    @endif
                    <a href="#contacto" class="hover:text-brand-600">Contacto</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-brand-500 px-4 py-2 text-white shadow-sm transition hover:bg-brand-600">
                            Ir a mi panel
                        </a>
                    @else
                        @if ($canLogin)
                            <a href="{{ route('login') }}" class="hover:text-brand-600">Iniciar sesión</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto max-w-6xl px-6 pb-16 pt-12 sm:pt-20">
                <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="text-center lg:text-left">
                        <img src="/images/brand/icono-rosa.jpg" alt="" class="mx-auto h-20 w-20 rounded-full shadow-lg shadow-brand-200 lg:mx-0">

                        <h1 class="mt-6 font-cursive text-5xl text-brand-600 sm:text-6xl">Melissa López N.</h1>
                        <p class="mt-2 text-sm font-semibold uppercase tracking-[0.3em] text-brand-400">Odontología integral</p>

                        <p class="mx-auto mt-6 max-w-xl text-base text-gray-600 lg:mx-0">
                            Cuidamos tu sonrisa con atención personalizada. Agenda tu cita en línea en unos minutos, sin necesidad de crear una cuenta.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-4 lg:justify-start">
                            <a href="{{ route('citas.publica.create') }}" class="rounded-md bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">
                                Agendar una cita
                            </a>
                        </div>
                    </div>

                    <img src="{{ asset(config('dentissa.imagenes.hero')) }}" alt="Dentista conversando con una paciente en un consultorio" fetchpriority="high" class="aspect-[16/9] w-full rounded-2xl object-cover shadow-xl shadow-brand-200">
                </div>

                <div class="mt-16 grid gap-6 text-left sm:grid-cols-3">
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-brand-600">Agenda en línea</h2>
                        <p class="mt-2 text-sm text-gray-600">Elige servicio, tratamiento y horario disponible sin llamadas ni esperas.</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-brand-600">Sin necesidad de cuenta</h2>
                        <p class="mt-2 text-sm text-gray-600">Agenda con tu nombre y teléfono, sin registros ni contraseñas que recordar.</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h2 class="font-semibold text-brand-600">Atención personalizada</h2>
                        <p class="mt-2 text-sm text-gray-600">Un equipo dedicado a tu salud dental, desde tu primera visita.</p>
                    </div>
                </div>
            </main>

            <x-asistente-chat />

            @if ($servicios->isNotEmpty())
                <section id="servicios" class="bg-white py-16">
                    <div class="mx-auto max-w-6xl px-6">
                        <h2 class="text-center text-2xl font-semibold text-brand-600">Nuestros servicios</h2>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($servicios as $servicio)
                                <div class="overflow-hidden rounded-lg border border-brand-100 bg-white">
                                    @if ($servicio->imagen)
                                        <img src="/storage/{{ $servicio->imagen }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                    @elseif ($imagenPredeterminada = ($imagenesServicios[$servicio->nombre] ?? null))
                                        <img src="{{ asset($imagenPredeterminada) }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-48 w-full items-center justify-center bg-brand-100 text-sm text-brand-400">
                                            Imagen próximamente
                                        </div>
                                    @endif
                                    <div class="p-6">
                                        <h3 class="font-semibold text-gray-900">{{ $servicio->nombre }}</h3>
                                        @if ($servicio->descripcion)
                                            <p class="mt-2 text-sm text-gray-600">{{ $servicio->descripcion }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($ofertas->isNotEmpty())
                <section id="ofertas" class="overflow-hidden py-16">
                    <div class="mx-auto max-w-6xl px-6">
                        <h2 class="text-center text-2xl font-semibold text-brand-600">Ofertas vigentes</h2>
                        <div class="mt-10">
                            @include('partials.oferta-carousel', ['ofertas' => $ofertas])
                        </div>
                    </div>
                </section>
            @endif

            @if ($casos->isNotEmpty())
                <section id="casos" class="py-16">
                    <div class="mx-auto max-w-6xl px-6">
                        <p class="text-center text-xs font-semibold uppercase tracking-[0.2em] text-brand-400">
                            Resultados que hablan por nosotros
                        </p>
                        <h2 class="mt-2 text-center text-2xl font-semibold text-brand-600">Casos reales</h2>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($casos as $caso)
                                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                                    @if ($caso->imagenes->isNotEmpty())
                                        <div class="flex h-40">
                                            @foreach ($caso->imagenes->take(2) as $imagen)
                                                <img src="/storage/{{ $imagen->url }}" alt="{{ $caso->titulo }}" class="h-40 w-full object-cover">
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="flex h-40 w-full items-center justify-center bg-brand-100 text-sm text-brand-400">
                                            Sin imagen
                                        </div>
                                    @endif
                                    <div class="p-6">
                                        <h3 class="font-semibold text-gray-900">{{ $caso->titulo }}</h3>
                                        @if ($caso->descripcion)
                                            <p class="mt-2 text-sm text-gray-600">{{ $caso->descripcion }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($testimonios->isNotEmpty())
                <section id="testimonios" class="bg-white py-16">
                    <div class="mx-auto max-w-6xl px-6">
                        <h2 class="text-center text-2xl font-semibold text-brand-600">Lo que dicen nuestros pacientes</h2>
                        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($testimonios as $testimonio)
                                <div class="rounded-lg border border-brand-100 p-6">
                                    <p class="text-amber-500">{{ str_repeat('★', $testimonio->calificacion) }}{{ str_repeat('☆', 5 - $testimonio->calificacion) }}</p>
                                    <p class="mt-2 text-sm text-gray-600">&quot;{{ $testimonio->comentario }}&quot;</p>
                                    <p class="mt-3 text-xs font-medium text-gray-400">— {{ $testimonio->paciente->nombre }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($galeria->isNotEmpty())
                <section id="galeria" class="py-16">
                    <div class="mx-auto max-w-6xl px-6">
                        <h2 class="text-center text-2xl font-semibold text-brand-600">Galería</h2>
                        <div class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            @foreach ($galeria as $imagen)
                                <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-32 w-full rounded-lg object-cover shadow-sm sm:h-40">
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            @if ($faqs->isNotEmpty())
                <section id="faq" class="bg-white py-16">
                    <div class="mx-auto max-w-3xl px-6">
                        <h2 class="text-center text-2xl font-semibold text-brand-600">Preguntas frecuentes</h2>
                        <div class="mt-10 space-y-3">
                            @foreach ($faqs as $faq)
                                <div x-data="{ abierta: false }" class="rounded-lg border border-brand-100">
                                    <button type="button" @click="abierta = ! abierta"
                                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-medium text-gray-900">
                                        <span>{{ $faq->pregunta }}</span>
                                        <span class="shrink-0 text-brand-500" x-text="abierta ? '−' : '+'"></span>
                                    </button>
                                    <div x-show="abierta" class="px-5 pb-4 text-sm text-gray-600">
                                        {{ $faq->respuesta }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <section id="contacto" class="bg-brand-500 py-16 text-white">
                <div class="mx-auto max-w-3xl px-6 text-center">
                    <h2 class="text-2xl font-semibold">Contacto</h2>
                    <p class="mt-2 text-brand-50">¿Tienes dudas o quieres agendar por otro medio? Escríbenos.</p>

                    <div class="mt-8 grid gap-4 text-sm sm:grid-cols-2">
                        <a href="tel:{{ $contacto['telefono'] }}" class="rounded-lg bg-white/10 p-4 hover:bg-white/20">
                            <p class="font-semibold">Teléfono</p>
                            <p class="mt-1">{{ $contacto['telefono'] }}</p>
                        </a>
                        <a href="https://wa.me/{{ $contacto['whatsapp'] }}" target="_blank" rel="noreferrer" class="rounded-lg bg-white/10 p-4 hover:bg-white/20">
                            <p class="font-semibold">WhatsApp</p>
                            <p class="mt-1">Escríbenos directo</p>
                        </a>
                        <a href="mailto:{{ $contacto['correo'] }}" class="rounded-lg bg-white/10 p-4 hover:bg-white/20">
                            <p class="font-semibold">Correo</p>
                            <p class="mt-1">{{ $contacto['correo'] }}</p>
                        </a>
                        <div class="rounded-lg bg-white/10 p-4">
                            <p class="font-semibold">Dirección y horario</p>
                            <p class="mt-1">{{ $contacto['direccion'] }}</p>
                            <p class="mt-1">{{ $contacto['horario_texto'] }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="border-t border-brand-100 py-8 text-center text-xs text-gray-400">
                Melissa López N. — Odontología Integral · {{ date('Y') }}
            </footer>
        </div>
    </body>
</html>
