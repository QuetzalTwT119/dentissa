<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Melissa López N. — Odontología Integral</title>

        @include('partials.pwa-meta')

        @php($heroImagePath = config('dentissa.imagenes.hero'))
        @php($heroImage = asset($heroImagePath).'?v='.filemtime(public_path($heroImagePath)))
        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="{{ $heroImage }}" as="image" fetchpriority="high">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @php($imagenesServicios = config('dentissa.imagenes.servicios', []))
        @php($serviciosPublicos = $servicios->isNotEmpty()
            ? $servicios
            : collect($imagenesServicios)->map(fn ($imagen, $nombre) => (object) [
                'nombre' => $nombre,
                'imagen' => null,
                'descripcion' => 'Atención profesional adaptada a las necesidades de tu sonrisa.',
            ]))
        <div class="min-h-screen bg-brand-50">
            <header class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="flex items-center gap-3">
                    <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N." class="h-12 w-12 object-contain">
                    <div class="leading-tight">
                        <p class="font-cursive text-2xl text-brand-600">Melissa López N.</p>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-brand-400">Odontología integral</p>
                    </div>
                </div>

                <nav class="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm font-medium text-gray-700">
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
                            <a href="{{ route('dashboard') }}" class="rounded-full bg-brand-600 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                            Ir a mi panel
                        </a>
                    @else
                        @if ($canLogin)
                            <a href="{{ route('login') }}" class="rounded-full border border-brand-200 px-5 py-2.5 font-semibold text-brand-700 transition hover:border-brand-400 hover:bg-white focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">Iniciar sesión</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 sm:pt-12 lg:px-8 lg:pb-20">
                <section class="relative isolate overflow-hidden rounded-[2rem] bg-[#fff8fa] px-5 py-9 sm:px-9 sm:py-12 lg:px-14 lg:py-14" aria-labelledby="inicio-titulo">
                    <div aria-hidden="true" class="absolute -right-20 -top-24 -z-10 h-72 w-72 rounded-full bg-brand-100/70 blur-3xl"></div>
                    <div class="grid items-center gap-9 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14">
                        <div class="text-center lg:text-left">
                            <p class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700">
                                <span class="h-2 w-2 rounded-full bg-brand-500" aria-hidden="true"></span>
                                Odontología integral
                            </p>
                            <p class="mt-6 font-cursive text-3xl text-brand-600 sm:text-4xl">Melissa López N.</p>
                            <h1 id="inicio-titulo" class="mx-auto mt-3 max-w-xl text-4xl font-semibold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:mx-0 lg:text-[3.4rem]">
                                Ir al dentista puede ser más sencillo.
                            </h1>
                            <p class="mx-auto mt-5 max-w-lg text-base leading-7 text-slate-600 sm:text-lg lg:mx-0">
                                Explora los tratamientos y agenda en línea eligiendo el servicio, la atención que necesitas y un horario disponible.
                            </p>
                            <div data-asistente-zona-reservada class="mt-8 flex flex-col justify-center gap-3 sm:flex-row lg:justify-start">
                                <a href="{{ route('citas.publica.create') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-200 transition hover:-translate-y-0.5 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                    Agendar una cita
                                </a>
                                <a href="#servicios" class="inline-flex min-h-12 items-center justify-center rounded-full border border-brand-200 bg-white px-7 py-3 text-sm font-semibold text-brand-800 transition hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                    Explorar tratamientos
                                </a>
                            </div>
                            <p class="mt-5 text-sm text-slate-500">Agenda en línea, sin necesidad de crear una cuenta.</p>
                        </div>

                        <div class="relative mx-auto w-full max-w-2xl lg:max-w-none">
                            <img src="{{ $heroImage }}" alt="Paciente conversa con su dentista en un consultorio luminoso" fetchpriority="high" decoding="sync" class="aspect-[4/3] w-full rounded-[1.5rem] object-cover shadow-2xl shadow-brand-200/70 sm:aspect-[16/10]">
                        </div>
                    </div>
                </section>

                <section class="mt-16 lg:mt-20" aria-labelledby="como-agendar-titulo">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Sin complicaciones</p>
                        <h2 id="como-agendar-titulo" class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Así puedes agendar</h2>
                    </div>
                    <ol class="mt-8 grid gap-4 sm:grid-cols-3 sm:gap-5">
                        <li class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm sm:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">01</span>
                            <h3 class="mt-4 font-semibold text-slate-900">Elige un servicio</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Revisa las opciones disponibles para encontrar la atención que buscas.</p>
                        </li>
                        <li class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm sm:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">02</span>
                            <h3 class="mt-4 font-semibold text-slate-900">Selecciona tratamiento</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Cuéntanos qué tratamiento necesitas para continuar con tu solicitud.</p>
                        </li>
                        <li class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm sm:p-6">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">03</span>
                            <h3 class="mt-4 font-semibold text-slate-900">Escoge un horario</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Consulta la disponibilidad y envía tu solicitud de cita en línea.</p>
                        </li>
                    </ol>
                </section>
            </main>

            <x-asistente-chat />

            @if ($serviciosPublicos->isNotEmpty())
                <section id="servicios" class="bg-white py-16">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div class="mx-auto max-w-2xl text-center">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Atención dental</p>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Explora nuestros servicios</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">Conoce las opciones disponibles y agenda en línea cuando encuentres la que buscas.</p>
                        </div>
                        <div class="mt-10">
                            <div class="mb-5 flex items-center justify-between gap-4 text-sm text-slate-500">
                                <p>{{ $serviciosPublicos->count() }} servicios disponibles</p>
                            </div>
                            <div class="servicios-marquee-mask overflow-hidden" role="region" aria-label="Servicios dentales disponibles">
                                <div class="servicios-marquee-track flex w-max">
                                    <div role="list" aria-label="Servicios dentales" class="flex shrink-0 gap-4 pr-4 sm:gap-5 sm:pr-5 lg:gap-6 lg:pr-6">
                                        @foreach ($serviciosPublicos as $servicio)
                                <article role="listitem" class="w-[84vw] max-w-[360px] shrink-0 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm transition duration-200 hover:shadow-xl hover:shadow-brand-100/70 sm:w-[min(44vw,360px)] lg:w-[min(28vw,360px)]">
                                    @if ($servicio->imagen)
                                        <img src="/storage/{{ $servicio->imagen }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                    @elseif ($imagenPredeterminada = ($imagenesServicios[$servicio->nombre] ?? null))
                                        <img src="{{ asset($imagenPredeterminada) }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-48 w-full items-center justify-center bg-brand-100 text-sm text-brand-400">
                                            Imagen próximamente
                                        </div>
                                    @endif
                                    <div class="p-5 sm:p-6">
                                        <h3 class="font-semibold text-slate-900">{{ $servicio->nombre }}</h3>
                                        @if ($servicio->descripcion)
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $servicio->descripcion }}</p>
                                        @endif
                                    </div>
                                </article>
                                        @endforeach
                                    </div>
                                    <div aria-hidden="true" class="flex shrink-0 gap-4 pr-4 sm:gap-5 sm:pr-5 lg:gap-6 lg:pr-6">
                                        @foreach ($serviciosPublicos as $servicio)
                                            <article class="w-[84vw] max-w-[360px] shrink-0 overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm sm:w-[min(44vw,360px)] lg:w-[min(28vw,360px)]">
                                                @if ($servicio->imagen)
                                                    <img src="/storage/{{ $servicio->imagen }}" alt="" class="h-48 w-full object-cover" loading="lazy">
                                                @elseif ($imagenPredeterminada = ($imagenesServicios[$servicio->nombre] ?? null))
                                                    <img src="{{ asset($imagenPredeterminada) }}" alt="" class="h-48 w-full object-cover" loading="lazy">
                                                @else
                                                    <div class="flex h-48 w-full items-center justify-center bg-brand-100 text-sm text-brand-400">Imagen próximamente</div>
                                                @endif
                                                <div class="p-5 sm:p-6">
                                                    <h3 class="font-semibold text-slate-900">{{ $servicio->nombre }}</h3>
                                                    @if ($servicio->descripcion)
                                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $servicio->descripcion }}</p>
                                                    @endif
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                            </div>
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

            <section id="contacto" class="bg-gradient-to-b from-brand-50 to-white py-16 sm:py-20">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-8 rounded-[2rem] border border-brand-100 bg-white p-6 shadow-sm sm:p-9 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-12 lg:p-12">
                        <div class="text-center lg:text-left">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Melissa López N. · Odontología integral</p>
                            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">¿Empezamos con tu cita?</h2>
                            <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-600 sm:text-base lg:mx-0">Agenda en línea y revisa los horarios disponibles. No necesitas crear una cuenta.</p>
                            <a href="{{ route('citas.publica.create') }}" class="mt-6 inline-flex min-h-12 items-center justify-center rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-md shadow-brand-200 transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">Agendar una cita</a>
                        </div>
                        @if (filled($contacto['telefono'] ?? null) || filled($contacto['correo'] ?? null) || filled($contacto['direccion'] ?? null) || filled($contacto['horario_texto'] ?? null) || filled($contacto['telegram_url'] ?? null) || (config('dentissa.modo_demo') && filled($contacto['telegram_usuario'] ?? null)))
                        <div class="rounded-2xl bg-brand-50 p-4 sm:p-6">
                            @if (config('dentissa.modo_demo'))
                                <p class="mb-4 inline-flex rounded-full border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-900">Información ficticia · No contactar</p>
                            @endif
                                <div class="grid gap-3 text-sm sm:grid-cols-2">
                                    @if (filled($contacto['telefono'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ config('dentissa.modo_demo') ? 'Teléfono de prueba' : 'Teléfono' }}</p>
                                            @if (config('dentissa.modo_demo'))<p class="mt-1 font-medium text-slate-800">{{ $contacto['telefono'] }}</p>@else<a href="tel:{{ $contacto['telefono'] }}" class="mt-1 inline-block font-medium text-slate-800 hover:text-brand-700">{{ $contacto['telefono'] }}</a>@endif
                                        </div>
                                    @endif
                                    @if (filled($contacto['correo'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ config('dentissa.modo_demo') ? 'Correo de prueba' : 'Correo' }}</p>
                                            @if (config('dentissa.modo_demo'))<p class="mt-1 break-all font-medium text-slate-800">{{ $contacto['correo'] }}</p>@else<a href="mailto:{{ $contacto['correo'] }}" class="mt-1 inline-block break-all font-medium text-slate-800 hover:text-brand-700">{{ $contacto['correo'] }}</a>@endif
                                        </div>
                                    @endif
                                    @if (config('dentissa.modo_demo') && filled($contacto['telegram_usuario'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telegram de prueba</p>
                                            <p class="mt-1 font-medium text-slate-800">{{ $contacto['telegram_usuario'] }}</p>
                                        </div>
                                    @elseif (filled($contacto['telegram_url'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telegram</p>
                                            <a href="{{ $contacto['telegram_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block font-medium text-slate-800 hover:text-brand-700">Contactar por Telegram</a>
                                        </div>
                                    @endif
                                    @if (filled($contacto['direccion'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dirección</p>
                                            <p class="mt-1 font-medium leading-5 text-slate-800">{{ $contacto['direccion'] }}</p>
                                        </div>
                                    @endif
                                    @if (filled($contacto['horario_texto'] ?? null))
                                        <div class="rounded-xl border border-brand-100 bg-white p-4">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ config('dentissa.modo_demo') ? 'Horario de demostración' : 'Horario' }}</p>
                                            <p class="mt-1 font-medium text-slate-800">{{ $contacto['horario_texto'] }}</p>
                                        </div>
                                    @endif
                                </div>
                        </div>
                        @endif
                    </div>
                </div>
            </section>

            <footer class="border-t border-brand-100 py-8 text-center text-xs text-gray-400">
                Melissa López N. — Odontología Integral · {{ date('Y') }}
            </footer>
        </div>
    </body>
</html>
