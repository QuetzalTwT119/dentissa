<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lista de espera — Dentissa Odontología Integral</title>
        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#FAF6F6] font-sans text-[#2D2526] selection:bg-[#f2b0a6] selection:text-[#2D2526]">
        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-12">
            <!-- Barra superior -->
            <div class="flex items-center justify-between pb-6">
                <a href="{{ route('citas.publica.create') }}" class="inline-flex items-center gap-2 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-2.5 text-xs font-bold text-[#403539] shadow-xs transition hover:border-brand-300 hover:text-brand-700 hover:-translate-y-0.5">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6M9 12h12" />
                    </svg>
                    <span>Volver a agendar</span>
                </a>

                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700 border border-brand-200">
                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    Disponibilidad Oportuna
                </span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start"
                 x-data="{ 
                    servicios: @js($servicios), 
                    servicio: @js(old('id_servicio', '')), 
                    get tratamientos() { return this.servicios.find(item => item.id === Number(this.servicio))?.tratamientos ?? []; } 
                 }">
                
                <!-- Columna Izquierda: Información de la Lista de Espera -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="overflow-hidden rounded-3xl border border-[#e9dfdb] bg-white shadow-xl">
                        <img src="{{ asset(config('dentissa.imagenes.lista_espera')) }}" alt="Paciente consultando la disponibilidad de citas dentales" class="aspect-[16/10] w-full object-cover" fetchpriority="high">
                        <div class="p-6">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-brand-700 border border-brand-200">
                                Huecos de Agenda
                            </span>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-[#2D2526]">Lista de espera</h1>
                            <p class="mt-2 text-sm leading-relaxed text-[#6b5e62]">Déjanos tus preferencias. El consultorio te contactará solo si se libera un horario compatible; no es una cita confirmada.</p>
                            
                            <div class="mt-6 space-y-3 pt-6 border-t border-[#e9dfdb]/70 text-xs text-[#403539]">
                                <div class="flex items-start gap-2.5">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-[10px]">✓</span>
                                    <span><strong>Prioridad por orden de registro:</strong> te contactamos en cuanto un paciente reprograma.</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-[10px]">✓</span>
                                    <span><strong>Flexibilidad total:</strong> si el horario no te conviene, conservas tu turno.</span>
                                </div>
                                <div class="flex items-start gap-2.5">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 font-bold text-[10px]">✓</span>
                                    <span><strong>Sin costo:</strong> registro 100% gratuito.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Formulario -->
                <div class="lg:col-span-7">
                    <div class="rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-xl sm:p-8">
                        @if (session('success'))
                            <div class="rounded-2xl border border-[#4E8D72]/30 bg-[#EAF4EE] p-6 text-sm font-semibold text-[#4E8D72] text-center shadow-xs">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-3">✓</div>
                                {{ session('success') }}
                            </div>
                        @else
                            <div class="mb-6 pb-4 border-b border-[#e9dfdb]">
                                <h2 class="text-xl font-bold tracking-tight text-[#2D2526]">Tus preferencias de atención</h2>
                                <p class="text-xs text-[#8A7A7D] mt-1">Completa tus datos de contacto y el rango en el que te gustaría asistir.</p>
                            </div>

                            <form method="POST" action="{{ route('atencion.lista-espera.store') }}" class="space-y-4">
                                @csrf
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div data-asistente-zona-reservada>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Nombre *</label>
                                        <input name="nombre" value="{{ old('nombre') }}" required placeholder="Tu nombre completo"
                                               class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    </div>
                                    <div data-asistente-zona-reservada>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Teléfono *</label>
                                        <input type="tel" name="telefono" value="{{ old('telefono') }}" required placeholder="10 dígitos"
                                               class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    </div>
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Correo (opcional)</label>
                                    <input type="email" name="email" value="{{ old('email') }}" placeholder="ejemplo@correo.com"
                                           class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div data-asistente-zona-reservada>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Servicio (opcional)</label>
                                        <select name="id_servicio" x-model="servicio" 
                                                class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                            <option value="">Cualquier servicio</option>
                                            <template x-for="item in servicios" :key="item.id">
                                                <option :value="item.id" x-text="item.nombre"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div data-asistente-zona-reservada x-show="tratamientos.length">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Tratamiento (opcional)</label>
                                        <select name="id_tratamiento" 
                                                class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                            <option value="">Cualquier tratamiento</option>
                                            <template x-for="item in tratamientos" :key="item.id">
                                                <option :value="item.id" x-text="item.nombre"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div data-asistente-zona-reservada>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Fecha preferida (opcional)</label>
                                        <input type="date" min="{{ now()->toDateString() }}" name="fecha_preferida" value="{{ old('fecha_preferida') }}" 
                                               class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                    </div>

                                    <div data-asistente-zona-reservada>
                                        <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Horario</label>
                                        <select name="franja_horaria" 
                                                class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                            <option value="cualquiera">Cualquier hora</option>
                                            <option value="manana">Turno Matutino (9:00 - 13:00)</option>
                                            <option value="tarde">Turno Vespertino (14:00 - 19:00)</option>
                                        </select>
                                    </div>
                                </div>

                                <div data-asistente-zona-reservada>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-[#382d31]">Notas (opcional)</label>
                                    <textarea name="notas" rows="3" placeholder="Indícanos días que no puedes o cualquier detalle para tu llamada..."
                                              class="mt-1.5 block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('notas') }}</textarea>
                                </div>

                                <div class="pt-2">
                                    <button data-asistente-zona-reservada class="w-full rounded-2xl bg-brand-600 px-6 py-4 text-sm font-bold text-white shadow-soft transition-all duration-200 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5">
                                        Unirme a la lista de espera
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </main>
        <x-asistente-chat />
    </body>
</html>
