<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Solicitar atención urgente — Dentissa Odontología Integral</title>
        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[#2D2526] selection:bg-[#f2b0a6] selection:text-[#2D2526] bg-[#FAF6F6]">
        <main class="min-h-screen bg-[#FAF6F6] px-4 py-6 pb-8 sm:px-6 sm:py-10 lg:pb-10">
            <div class="mx-auto max-w-screen-2xl">
                <!-- Barra superior con regreso y marca -->
                <div class="flex items-center justify-between">
                    <a href="{{ route('citas.publica.create') }}" class="inline-flex items-center gap-2 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-xs font-bold text-[#403539] shadow-xs transition hover:border-brand-300 hover:text-brand-700 hover:bg-white hover:-translate-y-0.5">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12" />
                        </svg>
                        <span>Volver a agendar</span>
                    </a>

                    <div class="hidden sm:flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 border border-rose-200">
                            <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                            Triage Prioritario
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid items-start gap-4 sm:mt-5 sm:gap-6 lg:grid-cols-[minmax(380px,0.95fr)_minmax(0,1.05fr)] lg:gap-6">
                    <!-- Tarjeta Izquierda Informativa / Reaseguramiento -->
                    <section class="overflow-hidden rounded-3xl border border-[#e9dfdb] bg-white shadow-xl">
                        <div class="relative">
                            <img src="{{ asset(config('dentissa.imagenes.urgencias')) }}" alt="Paciente recibiendo orientación dental urgente" class="aspect-[2/1] w-full object-cover object-center sm:aspect-[16/9] lg:aspect-[16/9]" fetchpriority="high">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            <div class="absolute bottom-4 left-4 right-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 backdrop-blur-md px-3 py-1 text-xs font-bold text-brand-700 shadow-sm">
                                    <span class="h-2 w-2 rounded-full bg-rose-500 animate-ping"></span>
                                    Atención el Mismo Día
                                </span>
                            </div>
                        </div>

                        <div class="p-6 sm:p-8">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-600 flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                Atención prioritaria
                            </p>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#2D2526] sm:mt-3 sm:text-3xl">Solicitar atención urgente</h1>
                            <p class="mt-3 text-sm leading-relaxed text-[#6b5e62] sm:mt-4 sm:text-base">Describe brevemente lo que ocurre. Un integrante del consultorio revisará la solicitud y te contactará. Este formulario no proporciona diagnóstico ni sustituye atención médica.</p>

                            <!-- Alerta de Emergencia Vital -->
                            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-5 text-sm leading-relaxed text-rose-950 sm:leading-6 shadow-xs">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-rose-200/80 text-rose-800 font-bold text-sm">!</span>
                                    <div>
                                        <p class="font-bold text-rose-900">Si es una emergencia que pone en riesgo la vida</p>
                                        <p class="mt-1 text-xs sm:text-sm text-rose-800">Si hay dificultad para respirar o tragar, sangrado que no se detiene o pérdida de conciencia, llama a emergencias o acude al servicio de urgencias más cercano.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-[#e9dfdb]/70 flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 text-brand-700 font-bold text-sm">
                                    ML
                                </div>
                                <div class="text-xs">
                                    <p class="font-bold text-[#2D2526]">Dra. Melissa López N.</p>
                                    <p class="text-[#8A7A7D]">Cuidado dental humanizado &bull; Respuesta rápida</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Tarjeta Derecha Formulario -->
                    <section class="rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-xl sm:p-8" aria-label="Formulario de solicitud urgente">
                        @if (session('success'))
                            <div role="status" class="rounded-2xl border border-[#4E8D72]/30 bg-[#EAF4EE] p-6 text-sm leading-relaxed text-[#4E8D72] font-semibold text-center shadow-xs">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-3">
                                    ✓
                                </div>
                                {{ session('success') }}
                            </div>
                        @else
                            <div class="mb-6 border-b border-[#e9dfdb] pb-5">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-brand-700 border border-brand-200">
                                    Formulario Clínico
                                </span>
                                <h2 class="mt-2 text-xl font-bold tracking-tight text-[#2D2526] sm:text-2xl">Cuéntanos cómo podemos ayudarte</h2>
                                <p class="mt-1.5 text-xs sm:text-sm leading-relaxed text-[#6b5e62]">Comparte tus datos y el motivo de la solicitud. Los campos con * son obligatorios.</p>
                            </div>

                            <form method="POST" action="{{ route('atencion.urgencia.store') }}" class="space-y-3 sm:space-y-4">
                                @csrf
                                <div data-asistente-zona-reservada>
                                    <label for="nombre" class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Nombre *</label>
                                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name" placeholder="Tu nombre completo"
                                           class="mt-1.5 block min-h-11 w-full rounded-2xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    @error('nombre')<p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label for="telefono" class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Teléfono para contactarte *</label>
                                    <input id="telefono" type="tel" name="telefono" value="{{ old('telefono') }}" required autocomplete="tel" placeholder="10 dígitos para llamada inmediata"
                                           class="mt-1.5 block min-h-11 w-full rounded-2xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    @error('telefono')<p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Correo electrónico <span class="font-normal text-[#8A7A7D]">(opcional)</span></label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="ejemplo@correo.com"
                                           class="mt-1.5 block min-h-11 w-full rounded-2xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    @error('email')<p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                                </div>

                                <fieldset data-asistente-zona-reservada class="pt-1">
                                    <legend class="text-xs font-bold uppercase tracking-wider text-[#382d31]">¿Qué señales reportas? <span class="font-normal text-[#8A7A7D]">(opcional)</span></legend>
                                    <div class="mt-2.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach (['dolor' => 'Dolor agudo o pulsátil', 'inflamacion' => 'Inflamación visible', 'sangrado' => 'Sangrado persistente', 'traumatismo' => 'Golpe o traumatismo', 'otro' => 'Otra molestia'] as $valor => $etiqueta)
                                            <label class="group flex min-h-11 cursor-pointer items-center gap-3 rounded-2xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-xs font-bold text-[#403539] transition-all duration-200 hover:border-brand-400 hover:bg-brand-50/20">
                                                <input type="checkbox" name="senales_reportadas[]" value="{{ $valor }}" @checked(in_array($valor, old('senales_reportadas', []))) 
                                                       class="h-4 w-4 rounded-md border-[#e9dfdb] text-brand-600 focus:ring-brand-400">
                                                <span class="group-hover:text-brand-700 transition-colors">{{ $etiqueta }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('senales_reportadas')<p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                                </fieldset>

                                <div data-asistente-zona-reservada>
                                    <label for="motivo" class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Cuéntanos brevemente qué ocurre *</label>
                                    <textarea id="motivo" name="motivo" required rows="4" placeholder="Describe la molestia, desde cuándo la sientes o si tuviste algún accidente dental..."
                                              class="mt-1.5 block min-h-28 w-full rounded-2xl border border-[#e9dfdb] bg-white p-3.5 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200 sm:min-h-32">{{ old('motivo') }}</textarea>
                                    @error('motivo')<p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada class="pt-3 sm:flex sm:justify-end">
                                    <button class="min-h-11 w-full rounded-2xl bg-brand-600 px-7 py-3.5 text-sm font-bold text-white shadow-soft transition-all duration-200 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto sm:min-w-52">
                                        Enviar solicitud
                                    </button>
                                </div>
                            </form>
                        @endif
                    </section>
                </div>
            </div>
        </main>
        <x-asistente-chat />
    </body>
</html>
