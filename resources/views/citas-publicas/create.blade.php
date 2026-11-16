<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#fbf7f3">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.favicon')

        <title>Agendar cita — Dentissa Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-italic.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-cream font-sans text-ink antialiased">
        <x-asistente-chat />

        @php
            // Clases compartidas del formulario (se definen una vez y se reutilizan).
            $claseCampo = 'block w-full min-h-12 rounded-xl border-line bg-white px-3.5 text-[0.95rem] text-ink shadow-2xs placeholder:text-muted/70 transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 aria-[invalid=true]:border-rose-400';
            $claseEtiqueta = 'mb-1.5 block text-sm font-semibold text-ink';
            $claseError = 'mt-1.5 text-xs font-medium text-rose-700';
            $claseAtras = 'btn-secondary flex-1 py-3 sm:flex-none sm:px-6';
            $claseSiguiente = 'btn-brand flex-[2] py-3 sm:flex-none sm:px-8';
        @endphp

        @if (session('success'))
            <main class="flex min-h-[100dvh] items-center justify-center px-4 py-10">
                <div class="w-full max-w-md rounded-4xl border border-line bg-white p-8 text-center shadow-lift sm:p-10">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sage-50 text-sage-600 ring-8 ring-sage-50/60">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="mt-6 firma text-2xl text-brand-600">Melissa López N.</p>
                    <h1 class="mt-3 text-[1.75rem] leading-tight text-ink">¡Listo! Tu cita quedó confirmada. Te esperamos.</h1>
                    @if ($resumen = session('cita_resumen'))
                        <dl class="mt-6 divide-y divide-line/80 rounded-2xl border border-line bg-cream text-left text-sm">
                            <div class="px-5 py-3.5">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Tratamiento</dt>
                                <dd class="mt-1 font-semibold text-ink">{{ $resumen['tratamiento'] }}</dd>
                            </div>
                            <div class="px-5 py-3.5">
                                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Fecha y hora</dt>
                                <dd class="mt-1 font-semibold text-brand-700">{{ ucfirst($resumen['fecha']) }} · {{ $resumen['hora'] }} hrs</dd>
                            </div>
                        </dl>
                    @endif
                    <p class="mt-5 text-sm leading-6 text-muted">Tu cita quedó registrada en el consultorio. Guarda estos datos o toma una captura; si necesitas cambiarla, comunícate con el consultorio.</p>
                    <a href="/" class="btn-brand mt-8 w-full py-3 sm:w-auto sm:px-8">
                        Volver al inicio
                    </a>
                </div>
            </main>
        @else
            <!-- Barra superior -->
            <header class="sticky top-0 z-20 border-b border-line/70 bg-cream/90 backdrop-blur-md supports-[backdrop-filter]:bg-cream/75">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                    <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3 rounded-full" aria-label="Volver al inicio">
                        <img src="/images/brand/logo-rosa-transparente.png" alt="Dentissa" class="h-10 w-10 shrink-0 rounded-full bg-white object-contain p-0.5 ring-1 ring-peach-200">
                        <span class="min-w-0 leading-none">
                            <span class="block truncate firma text-[1.1rem] leading-none text-brand-700 sm:text-[1.2rem]">Dra. Melissa López N.</span>
                            <span class="mt-1 hidden text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-muted sm:block">Odontología Integral & Armonización</span>
                        </span>
                    </a>

                    <a href="{{ url('/') }}" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-full border border-line bg-white px-3 text-sm sm:px-4 font-semibold text-ink transition-colors hover:border-peach-300 hover:text-brand-700" aria-label="Volver al inicio">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Inicio</span>
                    </a>
                </div>
            </header>

            <main class="pt-6 pb-28 sm:pb-16 sm:pt-10"
                 x-data="{
                    paso: {{ $errors->any() ? 4 : 0 }},
                    pasos: ['Tipo de atención', 'Fecha', 'Hora', 'Resumen', 'Datos'],
                    encabezados: [
                        { etiqueta: 'Selección clínica', titulo: '¿Qué tipo de atención necesitas?', texto: 'Selecciona la especialidad para consultar la agenda disponible de la Dra. Melissa López.' },
                        { etiqueta: 'Calendario', titulo: 'Seleccionar una fecha', texto: 'Elige el día en el que deseas programar tu valoración en Dentissa.' },
                        { etiqueta: 'Horario', titulo: 'Elige una hora', texto: 'Turnos confirmados en tiempo real con la agenda del consultorio.' },
                        { etiqueta: 'Confirmación', titulo: 'Resumen de la cita', texto: 'Revisa el tratamiento, la fecha y la hora antes de continuar' },
                        { etiqueta: 'Datos de contacto', titulo: 'Datos para agendar', texto: 'Los usamos para confirmar tu cita médica y enviarte recordatorios.' },
                    ],
                    servicios: @js($servicios),
                    idServicio: @js(old('id_servicio', '')),
                    idTratamiento: @js(old('id_tratamiento', '')),
                    fecha: @js(old('fecha', '')),
                    hora: @js(old('hora', null)),
                    horarios: [],
                    loadingHorarios: false,
                    descripciones: {
                        'Odontología General': 'Valoración, limpieza dental, retiro de caries y extracciones para toda la familia.',
                        'Odontología Estética': 'Blanqueamiento dental para devolver la luminosidad natural a tu sonrisa.',
                        'Ortodoncia': 'Alineación dental con brackets estéticos y alineadores invisibles de precisión.',
                        'Implantología': 'Rehabilitación y reposición de piezas con implantes dentales de titanio.',
                        'Periodoncia': 'Tratamiento especializado de encías, hueso de soporte y salud bucal.',
                        'Endodoncia': 'Terapia de conductos radiculares para aliviar dolor y salvar tus piezas naturales.',
                        'Odontopediatría': 'Cuidado dental preventivo y amable diseñado especialmente para niñas y niños.'
                    },
                    // 2026-11-18 -> miércoles 18 de noviembre de 2026 (se arma a mediodía local para evitar desfases de zona horaria).
                    // Sin comillas dobles: este código vive dentro del atributo x-data y una comilla doble lo cierra.
                    get fechaLegible() {
                        if (!this.fecha) return '';
                        const [anio, mes, dia] = this.fecha.split('-').map(Number);
                        return new Date(anio, mes - 1, dia, 12).toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                    },
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
                 x-init="$watch('paso', () => { if ($refs.tarjeta.getBoundingClientRect().top < 0) $refs.tarjeta.scrollIntoView({ block: 'start' }) })"
            >
                <div class="mx-auto grid max-w-6xl grid-cols-1 items-start gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.75fr)] lg:gap-8">

                    <!-- Tarjeta principal con los pasos -->
                    <section x-ref="tarjeta" class="w-full scroll-mt-20 overflow-hidden rounded-4xl border border-line bg-white shadow-soft">

                        <!-- Encabezado del paso actual (uno solo para los 5 pasos) -->
                        <div class="border-b border-line/80 bg-gradient-to-b from-peach-50 to-white px-5 pb-6 pt-6 sm:px-8 sm:pt-8">
                            <p class="eyebrow">
                                <span x-text="`Paso ${paso + 1} de ${pasos.length}`">Paso 1 de 5</span>
                                <span aria-hidden="true">·</span>
                                <span x-text="encabezados[paso].etiqueta">Selección clínica</span>
                            </p>
                            <h1 class="mt-3 text-[1.75rem] leading-tight text-ink sm:text-[2.1rem]"
                                x-text="paso === 0 && servicioSeleccionado ? 'Elige un tratamiento' : encabezados[paso].titulo">¿Qué tipo de atención necesitas?</h1>
                            <p class="mt-2 max-w-lg text-sm leading-6 text-muted" x-text="paso === 0 && servicioSeleccionado ? 'Selecciona el procedimiento que necesitas; cada uno muestra su duración aproximada.' : encabezados[paso].texto">Selecciona la especialidad para consultar la agenda disponible de la Dra. Melissa López.</p>

                            <div class="mt-5 flex gap-1.5" aria-hidden="true">
                                <template x-for="(p, i) in pasos" :key="i">
                                    <span class="h-1.5 flex-1 rounded-full transition-colors duration-300"
                                          :class="i < paso ? 'bg-sage-500' : (i === paso ? 'bg-brand-500' : 'bg-line')"></span>
                                </template>
                            </div>
                        </div>

                        <!-- PASO 0: SERVICIO Y TRATAMIENTO -->
                        <template x-if="paso === 0">
                            <div class="p-5 sm:p-8">
                                <!-- Especialidades -->
                                <template x-if="!servicioSeleccionado">
                                    <div class="grid gap-3">
                                        <template x-for="servicio in servicios" :key="servicio.id">
                                            <button type="button"
                                                    @click="idServicio = servicio.id; idTratamiento = ''"
                                                    class="group flex w-full items-center gap-4 rounded-2xl border border-line bg-white p-4 text-left transition-[border-color,background-color,transform] duration-150 ease-out hover:border-peach-300 hover:bg-peach-50/50 active:scale-[0.99] sm:p-5">
                                                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200 transition-colors group-hover:bg-white sm:flex">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-serif text-lg leading-snug text-ink" x-text="servicio.nombre"></span>
                                                    <span class="mt-0.5 block text-sm leading-5 text-muted line-clamp-2" x-text="descripciones[servicio.nombre] || 'Atención personalizada y diagnóstico oportuno'"></span>
                                                </span>
                                                <svg class="h-5 w-5 shrink-0 text-muted transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Tratamientos de la especialidad elegida -->
                                <template x-if="servicioSeleccionado">
                                    <div>
                                        <div class="mb-6 flex items-center justify-between gap-3 rounded-2xl bg-cream p-3 pl-4 ring-1 ring-inset ring-line">
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Especialidad elegida</p>
                                                <p class="font-serif text-lg leading-snug text-ink" x-text="servicioSeleccionado.nombre"></p>
                                            </div>
                                            <button type="button"
                                                    @click="idServicio = ''; idTratamiento = ''"
                                                    class="inline-flex min-h-11 shrink-0 items-center rounded-full bg-white px-4 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-line transition-colors hover:ring-peach-300">
                                                Cambiar
                                            </button>
                                        </div>

                                        <p id="titulo-tratamientos" class="mb-3 text-sm font-semibold text-ink">Selecciona el procedimiento específico</p>

                                        <div class="grid gap-2.5" role="radiogroup" aria-labelledby="titulo-tratamientos">
                                            <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                                <button type="button"
                                                        role="radio"
                                                        :aria-checked="(Number(idTratamiento) === tratamiento.id).toString()"
                                                        @click="idTratamiento = tratamiento.id"
                                                        class="flex w-full items-center justify-between gap-3 rounded-2xl border p-4 text-left transition-[border-color,background-color] duration-150"
                                                        :class="Number(idTratamiento) === tratamiento.id
                                                            ? 'border-brand-400 bg-brand-50/60 ring-1 ring-brand-400'
                                                            : 'border-line bg-white hover:border-peach-300'">
                                                    <span class="flex min-w-0 items-center gap-3">
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                                              :class="Number(idTratamiento) === tratamiento.id ? 'border-brand-600 bg-brand-600' : 'border-line bg-white'">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="Number(idTratamiento) === tratamiento.id"></span>
                                                        </span>
                                                        <span class="text-[0.95rem] font-semibold text-ink" x-text="tratamiento.nombre"></span>
                                                    </span>
                                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-medium text-muted">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span x-text="tratamiento.duracion_estimada + ' min'"></span>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>

                                        <div class="mt-6 flex">
                                            <button type="button" :disabled="!idTratamiento" @click="siguiente()" class="btn-brand w-full py-3 sm:ml-auto sm:w-auto sm:px-8">
                                                <span>Continuar a fecha y horario</span>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- PASO 1: SELECCIONAR FECHA -->
                        <template x-if="paso === 1">
                            <div class="space-y-6 p-5 sm:p-8">
                                <div>
                                    <label for="fecha-picker" class="{{ $claseEtiqueta }}">Selecciona el día</label>
                                    <input id="fecha-picker" type="date" min="{{ now()->toDateString() }}"
                                           x-model="fecha" @change="cargarHorarios()"
                                           class="{{ $claseCampo }} font-semibold">
                                    <p class="mt-2 text-sm text-muted">Horario regular: {{ $horarioRegular }}</p>
                                </div>

                                <div class="flex gap-3 sm:justify-between">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    <button type="button" :disabled="!fecha" @click="siguiente()" class="{{ $claseSiguiente }}">Continuar a horario</button>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 2: ELEGIR HORA -->
                        <template x-if="paso === 2">
                            <div class="space-y-6 p-5 sm:p-8">
                                <div>
                                    <p class="mb-3 text-sm font-semibold text-ink">Horarios disponibles para el día seleccionado</p>
                                    @include('partials.disponibilidad-picker')
                                </div>

                                <div class="flex gap-3 sm:justify-between">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    <button type="button" :disabled="!hora" @click="siguiente()" class="{{ $claseSiguiente }}">Revisar resumen</button>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 3: RESUMEN DE LA CITA -->
                        <template x-if="paso === 3">
                            <div class="space-y-6 p-5 sm:p-8">
                                <div class="overflow-hidden rounded-3xl border border-line">
                                    <div class="bg-cream px-5 py-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Consulta en Dentissa</p>
                                        <h3 class="mt-1 font-serif text-xl text-ink" x-text="tratamientoSeleccionado?.nombre"></h3>
                                    </div>
                                    <dl class="grid grid-cols-1 divide-y divide-line/80 border-t border-line sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                                        <div class="px-5 py-4">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Especialidad</dt>
                                            <dd class="mt-1 font-semibold text-ink" x-text="servicioSeleccionado?.nombre"></dd>
                                        </div>
                                        <div class="px-5 py-4">
                                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Fecha y hora</dt>
                                            <dd class="mt-1 font-semibold text-brand-700" x-text="fechaLegible + ' — ' + hora + ' hrs'"></dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="flex gap-3 sm:justify-between">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    <button type="button" @click="siguiente()" class="{{ $claseSiguiente }}">Continuar a mis datos</button>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 4: DATOS DEL PACIENTE -->
                        <template x-if="paso === 4">
                            <form method="POST" action="{{ route('citas.publica.store') }}" class="space-y-5 p-5 sm:p-8">
                                @csrf
                                <input type="hidden" name="id_servicio" :value="idServicio">
                                <input type="hidden" name="id_tratamiento" :value="idTratamiento">
                                <input type="hidden" name="fecha" :value="fecha">
                                <input type="hidden" name="hora" :value="hora">

                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <label for="nombre" class="{{ $claseEtiqueta }}">Nombre <span class="text-brand-600" aria-hidden="true">*</span></label>
                                        <input id="nombre" type="text" name="nombre" autocomplete="given-name" maxlength="100" required
                                               placeholder="Ej. Ana" value="{{ old('nombre') }}"
                                               aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}"
                                               @if ($errors->has('nombre')) aria-describedby="nombre-error" @endif
                                               class="{{ $claseCampo }}">
                                        @error('nombre')
                                            <p id="nombre-error" role="alert" class="{{ $claseError }}">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="apellido" class="{{ $claseEtiqueta }}">Apellido <span class="text-brand-600" aria-hidden="true">*</span></label>
                                        <input id="apellido" type="text" name="apellido" autocomplete="family-name" maxlength="100" required
                                               placeholder="Ej. Pérez" value="{{ old('apellido') }}"
                                               aria-invalid="{{ $errors->has('apellido') ? 'true' : 'false' }}"
                                               @if ($errors->has('apellido')) aria-describedby="apellido-error" @endif
                                               class="{{ $claseCampo }}">
                                        @error('apellido')
                                            <p id="apellido-error" role="alert" class="{{ $claseError }}">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="telefono" class="{{ $claseEtiqueta }}">Teléfono <span class="text-brand-600" aria-hidden="true">*</span></label>
                                        <input id="telefono" type="tel" name="telefono" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" title="Ingresa exactamente 10 dígitos numéricos." required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                               placeholder="Ej. 5512345678" value="{{ old('telefono') }}"
                                               aria-invalid="{{ $errors->has('telefono') ? 'true' : 'false' }}"
                                               @if ($errors->has('telefono')) aria-describedby="telefono-error" @endif
                                               class="{{ $claseCampo }}">
                                        @error('telefono')
                                            <p id="telefono-error" role="alert" class="{{ $claseError }}">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="email" class="{{ $claseEtiqueta }}">Correo electrónico <span class="text-brand-600" aria-hidden="true">*</span></label>
                                        <input id="email" type="email" name="email" autocomplete="email" maxlength="100" required
                                               placeholder="Ej. ana@correo.com" value="{{ old('email') }}"
                                               aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                               @if ($errors->has('email')) aria-describedby="email-error" @endif
                                               class="{{ $claseCampo }}">
                                        @error('email')
                                            <p id="email-error" role="alert" class="{{ $claseError }}">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="motivo" class="{{ $claseEtiqueta }}">Motivo de la visita <span class="font-normal text-muted">(opcional)</span></label>
                                    <textarea id="motivo" name="motivo" placeholder="Cuéntanos brevemente si tienes alguna molestia o si es revisión de rutina..." rows="3"
                                              class="{{ $claseCampo }} py-3">{{ old('motivo') }}</textarea>
                                </div>

                                @error('hora')
                                    <p role="alert" class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</p>
                                @enderror

                                @error('envios')
                                    <p role="alert" class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</p>
                                @enderror

                                <div class="flex gap-3 pt-1 sm:justify-between">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    <button type="submit" class="{{ $claseSiguiente }}">Agendar cita</button>
                                </div>
                            </form>
                        </template>
                    </section>

                    <!-- Resumen lateral (ticket) -->
                    <aside class="w-full self-start rounded-4xl border border-line bg-white p-5 shadow-soft sm:p-7 lg:sticky lg:top-24">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="eyebrow">Agenda en línea</p>
                                <h2 class="mt-2 text-2xl text-ink">Tu cita, paso a paso</h2>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-muted">Elige lo que necesitas y revisa la disponibilidad antes de confirmar.</p>

                        <!-- Progreso -->
                        <div class="mt-5 hidden rounded-2xl bg-cream p-4 lg:block" role="progressbar" aria-label="Progreso de la cita" aria-valuemin="1" :aria-valuemax="pasos.length" :aria-valuenow="paso + 1">
                            <div class="flex items-center justify-between gap-3 text-xs font-semibold">
                                <span class="text-muted">Paso actual</span>
                                <span class="text-brand-700" x-text="`Paso ${paso + 1} de ${pasos.length}`"></span>
                            </div>
                            <p class="mt-1 text-sm font-semibold text-ink" x-text="pasos[paso]"></p>
                        </div>

                        <!-- Datos seleccionados -->
                        <div class="mt-5 rounded-2xl border border-dashed border-peach-300 p-4" aria-live="polite">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-700">Resumen de la cita</p>
                            <dl class="mt-2 divide-y divide-line/80 text-sm">
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Servicio</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!servicioSeleccionado && 'font-normal text-muted/80'" x-text="servicioSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Tratamiento</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!tratamientoSeleccionado && 'font-normal text-muted/80'" x-text="tratamientoSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Fecha</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!fecha && 'font-normal text-muted/80'" x-text="fechaLegible || 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Hora</dt>
                                    <dd class="min-w-0 text-right font-semibold text-brand-700" :class="!hora && 'font-normal text-muted/80'" x-text="hora || 'Pendiente'"></dd>
                                </div>
                            </dl>
                        </div>

                        <p class="mt-4 text-center text-xs leading-5 text-muted">Elige un horario disponible y completa tus datos para reservar tu cita.</p>
                    </aside>
                </div>
            </main>
        @endif
    </body>
</html>
