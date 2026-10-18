<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Solicitar atención urgente</title>
        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900">
        <main class="min-h-screen bg-brand-50 px-4 py-6 pb-8 sm:px-6 sm:py-10 lg:pb-10">
            <div class="mx-auto max-w-screen-2xl">
                <a href="{{ route('citas.publica.create') }}" class="inline-flex items-center gap-2 rounded-md text-sm font-medium text-gray-600 transition hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-500">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12" />
                    </svg>
                    Volver a agendar
                </a>

                <div class="mt-4 grid items-start gap-4 sm:mt-5 sm:gap-6 lg:grid-cols-[minmax(380px,0.95fr)_minmax(0,1.05fr)] lg:gap-6">
                    <section class="overflow-hidden rounded-2xl bg-white shadow-lg">
                        <img src="{{ asset(config('dentissa.imagenes.urgencias')) }}" alt="Paciente recibiendo orientación dental urgente" class="aspect-[2/1] w-full object-cover object-center sm:aspect-[16/9] lg:aspect-[16/9]" fetchpriority="high">
                        <div class="p-5 sm:p-6 lg:p-7">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Atención prioritaria</p>
                            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 sm:mt-3 sm:text-3xl">Solicitar atención urgente</h1>
                            <p class="mt-3 text-sm leading-6 text-gray-600 sm:mt-4 sm:text-base">Describe brevemente lo que ocurre. Un integrante del consultorio revisará la solicitud y te contactará. Este formulario no proporciona diagnóstico ni sustituye atención médica.</p>

                            <div class="mt-4 rounded-xl border border-red-100 bg-red-50 p-4 text-sm leading-5 text-red-900 sm:mt-5 sm:leading-6">
                                <p class="font-semibold">Si es una emergencia que pone en riesgo la vida</p>
                                <p class="mt-1">Si hay dificultad para respirar o tragar, sangrado que no se detiene o pérdida de conciencia, llama a emergencias o acude al servicio de urgencias más cercano.</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl bg-white p-5 shadow-lg sm:p-6 lg:p-7" aria-label="Formulario de solicitud urgente">
                        @if (session('success'))
                            <div role="status" class="rounded-xl border border-green-100 bg-green-50 p-5 text-sm leading-6 text-green-800">{{ session('success') }}</div>
                        @else
                            <div class="mb-5 border-b border-gray-100 pb-4 sm:mb-6 sm:pb-5">
                                <h2 class="text-lg font-semibold text-gray-900 sm:text-xl">Cuéntanos cómo podemos ayudarte</h2>
                                <p class="mt-1.5 text-sm leading-6 text-gray-600 sm:mt-2">Comparte tus datos y el motivo de la solicitud. Los campos con * son obligatorios.</p>
                            </div>

                            <form method="POST" action="{{ route('atencion.urgencia.store') }}" class="space-y-3 sm:space-y-4">
                                @csrf
                                <div data-asistente-zona-reservada>
                                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required autocomplete="name" class="mt-1.5 block min-h-11 w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('nombre')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono para contactarte *</label>
                                    <input id="telefono" type="tel" name="telefono" value="{{ old('telefono') }}" required autocomplete="tel" class="mt-1.5 block min-h-11 w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('telefono')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico <span class="font-normal text-gray-500">(opcional)</span></label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" class="mt-1.5 block min-h-11 w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <fieldset data-asistente-zona-reservada>
                                    <legend class="text-sm font-medium text-gray-700">¿Qué señales reportas? <span class="font-normal text-gray-500">(opcional)</span></legend>
                                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach (['dolor' => 'Dolor', 'inflamacion' => 'Inflamación', 'sangrado' => 'Sangrado', 'traumatismo' => 'Golpe o traumatismo', 'otro' => 'Otro'] as $valor => $etiqueta)
                                            <label class="flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:border-brand-200 hover:bg-brand-50">
                                                <input type="checkbox" name="senales_reportadas[]" value="{{ $valor }}" @checked(in_array($valor, old('senales_reportadas', []))) class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                {{ $etiqueta }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('senales_reportadas')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                                </fieldset>

                                <div data-asistente-zona-reservada>
                                    <label for="motivo" class="block text-sm font-medium text-gray-700">Cuéntanos brevemente qué ocurre *</label>
                                    <textarea id="motivo" name="motivo" required rows="4" class="mt-1.5 block min-h-28 w-full rounded-lg border-gray-300 px-3 py-2 shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:min-h-32">{{ old('motivo') }}</textarea>
                                    @error('motivo')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>

                                <div data-asistente-zona-reservada class="sm:flex sm:justify-end">
                                    <button class="min-h-11 w-full rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:w-auto sm:min-w-52">
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
