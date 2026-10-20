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
        <div class="min-h-screen bg-[#faf7f4]">
            <header class="mx-auto flex max-w-7xl flex-col gap-5 border-b border-[#e9dfdb] px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="flex items-center gap-3">
                    <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N." class="h-12 w-12 object-contain">
                    <div class="leading-tight">
                        <p class="font-cursive text-2xl text-brand-600">Melissa López N.</p>
                        <p class="text-xs font-medium uppercase tracking-[0.2em] text-brand-400">Odontología integral</p>
                    </div>
                </div>

                <nav class="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm font-medium text-[#51464a]">
                    <a href="#servicios" class="transition hover:text-brand-700">Servicios</a>
                    @if ($ofertas->isNotEmpty())
                        <a href="#ofertas" class="transition hover:text-brand-700">Ofertas</a>
                    @endif
                    @if ($casos->isNotEmpty())
                        <a href="#casos" class="transition hover:text-brand-700">Casos</a>
                    @endif
                    @if ($testimonios->isNotEmpty())
                        <a href="#testimonios" class="transition hover:text-brand-700">Testimonios</a>
                    @endif
                    @if ($galeria->isNotEmpty())
                        <a href="#galeria" class="transition hover:text-brand-700">Galería</a>
                    @endif
                    @if ($faqs->isNotEmpty())
                        <a href="#faq" class="transition hover:text-brand-700">Preguntas frecuentes</a>
                    @endif
                    <a href="#contacto" class="transition hover:text-brand-700">Contacto</a>

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

            <main class="mx-auto max-w-7xl px-4 pb-16 pt-8 sm:px-6 sm:pt-10 lg:px-8 lg:pb-20">
                <section class="grid items-center gap-10 py-5 sm:gap-12 lg:min-h-[570px] lg:grid-cols-[0.88fr_1.12fr] lg:gap-16 lg:py-10" aria-labelledby="inicio-titulo">
                    <div class="relative z-10 text-center lg:py-12 lg:text-left">
                        <p class="flex items-center justify-center gap-3 text-xs font-semibold uppercase tracking-[0.22em] text-brand-700 lg:justify-start">
                            <span class="h-px w-9 bg-brand-500" aria-hidden="true"></span>
                            Odontología integral · Ciudad de México
                        </p>
                        <p class="mt-8 font-cursive text-3xl text-brand-600 sm:text-4xl">Melissa López N.</p>
                        <h1 id="inicio-titulo" class="mx-auto mt-3 max-w-xl text-[2.7rem] font-medium leading-[1.08] tracking-[-0.045em] text-[#211a20] sm:text-6xl lg:mx-0 lg:text-[4.25rem]">
                            Tu sonrisa, <span class="text-brand-700">en manos que te conocen.</span>
                        </h1>
                        <p class="mx-auto mt-6 max-w-lg text-base leading-7 text-[#665b5e] sm:text-lg sm:leading-8 lg:mx-0">
                            Atención dental cercana, con tiempo para escucharte y un plan pensado para ti.
                        </p>
                        <div data-asistente-zona-reservada class="mt-8 flex flex-col justify-center gap-4 sm:flex-row lg:justify-start">
                            <a href="{{ route('citas.publica.create') }}" class="inline-flex min-h-12 items-center justify-center rounded-lg bg-[#a92f58] px-7 py-3 text-sm font-semibold text-white transition hover:bg-[#8f2448] focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                Agendar una cita <span class="ml-3 text-lg" aria-hidden="true">→</span>
                            </a>
                            <a href="#servicios" class="inline-flex min-h-12 items-center justify-center px-4 py-3 text-sm font-semibold text-[#51464a] transition hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:ring-offset-2">
                                Conocer tratamientos <span class="ml-2" aria-hidden="true">↓</span>
                            </a>
                        </div>
                        <p class="mt-5 text-sm text-[#817579]">Puedes revisar horarios sin crear una cuenta.</p>
                    </div>

                    <figure class="relative mx-auto w-full max-w-2xl lg:max-w-none">
                        <div aria-hidden="true" class="absolute -bottom-4 -left-4 -z-10 h-2/3 w-2/3 border-b border-l border-brand-300 sm:-bottom-6 sm:-left-6"></div>
                        <img src="{{ $heroImage }}" alt="Paciente conversa con su dentista en un consultorio luminoso" fetchpriority="high" decoding="sync" class="aspect-[4/3] w-full rounded-tl-[4.5rem] rounded-br-[4.5rem] object-cover sm:aspect-[16/10]">
                        <figcaption class="mt-5 flex items-center gap-3 text-xs uppercase tracking-[0.16em] text-[#817579]">
                            <span class="h-px w-8 bg-brand-400" aria-hidden="true"></span>
                            Un espacio tranquilo para cuidar de ti
                        </figcaption>
                    </figure>
                </section>

                <section class="mt-12 border-t border-[#e9dfdb] pt-9 lg:mt-16" aria-labelledby="como-agendar-titulo">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Tu visita, a tu ritmo</p>
                            <h2 id="como-agendar-titulo" class="mt-2 text-2xl font-medium tracking-tight text-[#211a20] sm:text-3xl">Tres pasos para encontrar tu horario</h2>
                        </div>
                        <a href="{{ route('citas.publica.create') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Empezar ahora <span aria-hidden="true">→</span></a>
                    </div>
                    <ol class="mt-7 grid gap-0 sm:grid-cols-3">
                        <li class="border-t-2 border-brand-500 py-5 sm:mr-7">
                            <span class="font-cursive text-2xl text-brand-600">01</span>
                            <h3 class="mt-3 font-semibold text-[#2c2428]">Cuéntanos qué necesitas</h3>
                            <p class="mt-2 text-sm leading-6 text-[#665b5e]">Elige el servicio y el tratamiento que buscas.</p>
                        </li>
                        <li class="border-t-2 border-[#d7c9c8] py-5 sm:mr-7">
                            <span class="font-cursive text-2xl text-brand-600">02</span>
                            <h3 class="mt-3 font-semibold text-[#2c2428]">Escoge un horario</h3>
                            <p class="mt-2 text-sm leading-6 text-[#665b5e]">Consulta los espacios disponibles en línea.</p>
                        </li>
                        <li class="border-t-2 border-[#d7c9c8] py-5">
                            <span class="font-cursive text-2xl text-brand-600">03</span>
                            <h3 class="mt-3 font-semibold text-[#2c2428]">Solicita tu cita</h3>
                            <p class="mt-2 text-sm leading-6 text-[#665b5e]">Déjanos tus datos y el consultorio te confirmará.</p>
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
                            <div role="list" aria-label="Servicios dentales" class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
                                @foreach ($serviciosPublicos as $servicio)
                                    <article role="listitem" class="flex h-full flex-col overflow-hidden rounded-2xl border border-brand-100 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-100/70">
                                        @if ($servicio->imagen)
                                            <img src="/storage/{{ $servicio->imagen }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                        @elseif ($imagenPredeterminada = ($imagenesServicios[$servicio->nombre] ?? null))
                                            <img src="{{ asset($imagenPredeterminada) }}" alt="{{ $servicio->nombre }}" class="h-48 w-full object-cover" loading="lazy">
                                        @else
                                            <div class="flex h-48 w-full items-center justify-center bg-brand-100 text-sm text-brand-400">
                                                Imagen próximamente
                                            </div>
                                        @endif
                                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                                            <h3 class="min-h-12 font-semibold text-slate-900">{{ $servicio->nombre }}</h3>
                                            @if ($servicio->descripcion)
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $servicio->descripcion }}</p>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
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
