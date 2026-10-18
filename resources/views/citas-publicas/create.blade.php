<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Agendar cita</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        @if (session('success'))
            <div class="flex min-h-screen items-center justify-center bg-brand-50 px-6">
                <div class="max-w-md rounded-xl bg-white p-10 text-center shadow-lg">
                    <p class="font-cursive text-3xl text-brand-600">Melissa López N.</p>
                    <h1 class="mt-6 text-xl font-semibold text-gray-900">¡Listo! {{ session('success') }}</h1>
                    <a href="/" class="mt-8 inline-block rounded-md bg-brand-500 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-600">
                        Volver al inicio
                    </a>
                </div>
            </div>
        @else
            <div class="min-h-screen bg-brand-50 pb-24 sm:pb-0"
                 x-data="{
                    paso: {{ $errors->any() ? 4 : 0 }},
                    pasos: ['Tipo de atención', 'Fecha', 'Hora', 'Resumen', 'Datos'],
                    servicios: @js($servicios),
                    idServicio: @js(old('id_servicio', '')),
                    idTratamiento: @js(old('id_tratamiento', '')),
                    fecha: @js(old('fecha', '')),
                    hora: @js(old('hora', null)),
                    horarios: [],
                    loadingHorarios: false,
                    get servicioSeleccionado() {
                        return this.servicios.find(s => s.id === Number(this.idServicio)) ?? null;
                    },
                    get tratamientoSeleccionado() {
                        return this.servicioSeleccionado?.tratamientos.find(t => t.id === Number(this.idTratamiento)) ?? null;
                    },
                    siguiente() { this.paso = Math.min(this.paso + 1, this.pasos.length - 1); },
                    anterior() { this.paso = Math.max(this.paso - 1, 0); },
                    async cargarHorarios() {
                        this.hora = null;
                        if (!this.idTratamiento || !this.fecha) { this.horarios = []; return; }
                        this.loadingHorarios = true;
                        this.horarios = await window.cargarHorariosDisponibles(this.idTratamiento, this.fecha);
                        this.loadingHorarios = false;
                    },
                 }"
            >
                <x-asistente-chat />

                <div class="mx-auto grid max-w-6xl grid-cols-1 items-start gap-6 px-4 pt-2 sm:px-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.8fr)] lg:gap-8">
                <div class="w-full overflow-hidden bg-white shadow-lg sm:rounded-xl">
                    <template x-if="paso === 0">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl" x-text="servicioSeleccionado ? 'Elige un tratamiento' : '¿Qué tipo de atención necesitas?'">¿Qué tipo de atención necesitas?</h1>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="space-y-3 p-6">
                                <a href="{{ url('/') }}" class="mb-1 inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500" aria-label="Volver al inicio">
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9v11.25h13.5V9M9.75 20.25v-6h4.5v6" />
                                    </svg>
                                    Inicio
                                </a>
                                <template x-if="!servicioSeleccionado">
                                    <div class="space-y-3">
                                        <template x-for="servicio in servicios" :key="servicio.id">
                                            <button type="button"
                                                    @click="idServicio = servicio.id; idTratamiento = ''"
                                                    class="block w-full rounded-lg border px-4 py-3 text-left font-medium text-gray-700 transition hover:border-brand-200">
                                                <span x-text="servicio.nombre"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="servicioSeleccionado">
                                    <div>
                                        <button type="button"
                                                @click="idServicio = ''; idTratamiento = ''"
                                                class="mb-5 block w-full rounded-lg bg-brand-50 px-4 py-3 text-left font-semibold text-gray-900 transition hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-400"
                                                x-text="servicioSeleccionado.nombre">
                                        </button>
                                        <p class="mb-2 text-sm font-medium text-gray-600">Selecciona un tratamiento</p>
                                        <div class="space-y-2">
                                            <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                                <button type="button"
                                                        @click="idTratamiento = tratamiento.id"
                                                        class="block w-full rounded-lg border px-4 py-3 text-left text-sm transition"
                                                        :class="Number(idTratamiento) === tratamiento.id ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-700 hover:border-brand-200'">
                                                    <span x-text="tratamiento.nombre"></span>
                                                    <span class="text-gray-400">(<span x-text="tratamiento.duracion_estimada"></span> min)</span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </template>

                                <button type="button" :disabled="!idTratamiento" @click="siguiente()"
                                        class="mt-4 w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-40">
                                    Continuar
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="paso === 1">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl">Seleccionar una fecha</h1>
                                <p class="mt-2 text-sm text-brand-50">Elige el día en el que quieres tu cita</p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="p-6">
                                <input type="date" :min="new Date().toISOString().split('T')[0]"
                                       x-model="fecha" @change="cargarHorarios()"
                                       class="block w-full rounded-md border-gray-300 text-lg shadow-sm focus:border-brand-500 focus:ring-brand-500">

                                <div class="mt-6 flex gap-3">
                                    <button type="button" @click="anterior()" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Atrás</button>
                                    <button type="button" :disabled="!fecha" @click="siguiente()" class="w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-40">Continuar</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="paso === 2">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl">Elige una hora</h1>
                                <p class="mt-2 text-sm text-brand-50">Horarios disponibles ese día</p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="p-6">
                                @include('partials.disponibilidad-picker')

                                <div class="mt-6 flex gap-3">
                                    <button type="button" @click="anterior()" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Atrás</button>
                                    <button type="button" :disabled="!hora" @click="siguiente()" class="w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-40">Continuar</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="paso === 3">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl">Resumen de la cita</h1>
                                <p class="mt-2 text-sm text-brand-50">Revisa el tratamiento, la fecha y la hora antes de continuar</p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="space-y-4 p-6">
                                <div class="rounded-lg bg-gray-50 p-4 text-center">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Tratamiento</p>
                                    <p class="text-lg font-semibold text-gray-900" x-text="tratamientoSeleccionado?.nombre"></p>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-4 text-center">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Fecha y hora</p>
                                    <p class="text-lg font-semibold text-gray-900" x-text="fecha + ' — ' + hora"></p>
                                </div>

                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="anterior()" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Atrás</button>
                                    <button type="button" @click="siguiente()" class="w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">Continuar a mis datos</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="paso === 4">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl">Datos para agendar</h1>
                                <p class="mt-2 text-sm text-brand-50">Los usamos para confirmar tu cita</p>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('citas.publica.store') }}" class="space-y-4 p-6">
                                @csrf
                                <input type="hidden" name="id_servicio" :value="idServicio">
                                <input type="hidden" name="id_tratamiento" :value="idTratamiento">
                                <input type="hidden" name="fecha" :value="fecha">
                                <input type="hidden" name="hora" :value="hora">

                                <p class="text-sm text-gray-600">Los campos marcados con * son obligatorios.</p>

                                <div>
                                    <label for="nombre" class="mb-1 block text-sm font-medium text-gray-700">Nombre *</label>
                                    <input id="nombre" type="text" name="nombre" autocomplete="given-name" maxlength="100" required
                                           placeholder="Ej. Ana" value="{{ old('nombre') }}"
                                           aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}"
                                           @if ($errors->has('nombre')) aria-describedby="nombre-error" @endif
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('nombre')
                                        <p id="nombre-error" role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="apellido" class="mb-1 block text-sm font-medium text-gray-700">Apellido *</label>
                                    <input id="apellido" type="text" name="apellido" autocomplete="family-name" maxlength="100" required
                                           placeholder="Ej. Pérez" value="{{ old('apellido') }}"
                                           aria-invalid="{{ $errors->has('apellido') ? 'true' : 'false' }}"
                                           @if ($errors->has('apellido')) aria-describedby="apellido-error" @endif
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('apellido')
                                        <p id="apellido-error" role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="telefono" class="mb-1 block text-sm font-medium text-gray-700">Teléfono *</label>
                                    <input id="telefono" type="tel" name="telefono" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" title="Ingresa exactamente 10 dígitos numéricos." required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                           placeholder="Ej. 5512345678" value="{{ old('telefono') }}"
                                           aria-invalid="{{ $errors->has('telefono') ? 'true' : 'false' }}"
                                           @if ($errors->has('telefono')) aria-describedby="telefono-error" @endif
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('telefono')
                                        <p id="telefono-error" role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Correo electrónico *</label>
                                    <input id="email" type="email" name="email" autocomplete="email" maxlength="100" required
                                           placeholder="Ej. ana@correo.com" value="{{ old('email') }}"
                                           aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                           @if ($errors->has('email')) aria-describedby="email-error" @endif
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('email')
                                        <p id="email-error" role="alert" class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="motivo" class="mb-1 block text-sm font-medium text-gray-700">Motivo de la visita (opcional)</label>
                                    <textarea id="motivo" name="motivo" placeholder="Cuéntanos brevemente qué necesitas" rows="2"
                                              class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('motivo') }}</textarea>
                                </div>

                                @error('hora')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="anterior()" class="w-full rounded-md border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Atrás</button>
                                    <button type="submit" class="w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-50">Agendar cita</button>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>

                <aside data-asistente-zona-reservada class="w-full rounded-2xl border border-brand-100 bg-white p-6 shadow-sm sm:p-7 lg:sticky lg:top-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-600">Agenda en línea</p>
                    <h2 class="mt-2 text-2xl font-semibold text-gray-900">Tu cita, paso a paso</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Elige lo que necesitas y revisa la disponibilidad antes de confirmar.</p>

                    <div class="mt-6" role="progressbar" aria-label="Progreso de la cita" aria-valuemin="1" :aria-valuemax="pasos.length" :aria-valuenow="paso + 1">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-medium text-gray-500">Paso actual</span>
                            <span class="text-xs font-semibold text-brand-700" x-text="`Paso ${paso + 1} de ${pasos.length}`"></span>
                        </div>
                        <p class="mt-1 text-base font-semibold text-gray-900" x-text="pasos[paso]"></p>
                        <div class="mt-3 flex gap-1.5" aria-hidden="true">
                            <template x-for="(etapa, indice) in pasos" :key="etapa">
                                <span class="h-1.5 flex-1 rounded-full transition-colors"
                                      :class="indice <= paso ? 'bg-brand-500' : 'bg-gray-200'"></span>
                            </template>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl bg-brand-50 p-4" aria-live="polite">
                        <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Tu selección</p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Servicio</dt>
                                <dd class="text-right font-medium text-gray-800" x-text="servicioSeleccionado?.nombre ?? 'Pendiente'"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Tratamiento</dt>
                                <dd class="text-right font-medium text-gray-800" x-text="tratamientoSeleccionado?.nombre ?? 'Pendiente'"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Fecha</dt>
                                <dd class="text-right font-medium text-gray-800" x-text="fecha || 'Pendiente'"></dd>
                            </div>
                            <div class="flex justify-between gap-3">
                                <dt class="text-gray-500">Hora</dt>
                                <dd class="text-right font-medium text-gray-800" x-text="hora || 'Pendiente'"></dd>
                            </div>
                        </dl>
                    </div>

                    <p class="mt-5 border-t border-gray-100 pt-4 text-xs leading-5 text-gray-500">La cita se solicita al completar tus datos y pulsar “Agendar cita”.</p>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <a data-asistente-zona-reservada href="{{ route('atencion.urgencia') }}" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 transition hover:bg-red-100">
                            <strong class="block">¿Dolor intenso o urgencia?</strong>
                            <span class="mt-1 block text-xs leading-5">Pide que el consultorio te contacte.</span>
                        </a>
                        <a data-asistente-zona-reservada href="{{ route('atencion.lista-espera') }}" class="rounded-xl border border-brand-200 bg-white p-4 text-sm text-brand-900 transition hover:bg-brand-50">
                            <strong class="block">¿No encuentras horario?</strong>
                            <span class="mt-1 block text-xs leading-5">Únete a la lista de espera.</span>
                        </a>
                    </div>
                </aside>
                </div>
            </div>

        @endif
    </body>
</html>
