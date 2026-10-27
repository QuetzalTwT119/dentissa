<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.favicon')

        <title>Agendar cita — Dentissa Odontología Integral</title>

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/figtree-latin-ext-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/alex-brush-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[#2D2526] selection:bg-[#f2b0a6] selection:text-[#2D2526] bg-[#FAF6F6] min-h-screen">
        @if (session('success'))
            <div class="flex min-h-screen items-center justify-center bg-[#FAF6F6] px-6">
                <div class="max-w-md w-full rounded-3xl border border-[#e9dfdb] bg-white p-10 text-center shadow-xl">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 text-brand-600 mb-6 shadow-soft">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="font-cursive text-3xl text-brand-600">Melissa López N.</p>
                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-[#2D2526]">¡Listo! Tu cita quedó confirmada. Te esperamos.</h1>
                    <p class="mt-2 text-sm text-[#8A7A7D]">Tu solicitud ha sido registrada correctamente en el consultorio. Te enviaremos un recordatorio previo a tu cita.</p>
                    <a href="/" class="mt-8 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-7 py-3.5 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5">
                        Volver al inicio
                    </a>
                </div>
            </div>
        @else
            <!-- Barra superior boutique -->
            <header class="border-b border-[#e9dfdb]/80 bg-white/90 backdrop-blur-md sticky top-0 z-20">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-3 group" aria-label="Volver al inicio">
                        <img src="/images/brand/logo-rosa-transparente.png" alt="Dentissa" class="h-10 w-auto object-contain transition group-hover:scale-105">
                        <div class="hidden sm:block">
                            <p class="text-sm font-bold text-[#2D2526] leading-none">Dra. Melissa López N.</p>
                            <p class="text-[11px] font-medium text-[#8A7A7D] mt-0.5">Odontología Integral & Armonización</p>
                        </div>
                    </a>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Citas en línea 24/7
                        </span>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-1.5 text-xs font-semibold text-[#403539] transition hover:bg-[#FAF6F6] hover:text-brand-700" aria-label="Volver al inicio">
                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Inicio</span>
                        </a>
                    </div>
                </div>
            </header>

            <div class="min-h-screen bg-[#FAF6F6] pb-24 sm:pb-0 pt-6"
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
                    descripciones: {
                        'Odontología General': 'Prevención, profilaxis profunda, resinas y valoración integral para toda la familia.',
                        'Odontología Estética': 'Diseño de sonrisa, carillas estéticas y blanqueamiento clínico de alta gama.',
                        'Ortodoncia': 'Alineación dental con brackets estéticos y alineadores invisibles de precisión.',
                        'Implantología': 'Rehabilitación y reposición de piezas con implantes dentales de titanio.',
                        'Periodoncia': 'Tratamiento especializado de encías, hueso de soporte y salud bucal.',
                        'Endodoncia': 'Terapia de conductos radiculares para aliviar dolor y salvar tus piezas naturales.',
                        'Odontopediatría': 'Cuidado dental preventivo y amable diseñado especialmente para niñas y niños.'
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
            >
                <x-asistente-chat />

                <div class="mx-auto grid max-w-6xl grid-cols-1 items-start gap-6 px-4 pt-2 sm:px-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.8fr)] lg:gap-8">
                    <!-- Contenedor Principal de Pasos -->
                    <div class="w-full overflow-hidden bg-white shadow-xl rounded-3xl border border-[#e9dfdb]/90 transition-all">
                        
                        <!-- PASO 0: SERVICIO Y TRATAMIENTO -->
                        <template x-if="paso === 0">
                            <div>
                                <!-- Banner Superior Elegante -->
                                <div class="relative overflow-hidden bg-gradient-to-br from-[#2D2526] via-[#3a2029] to-[#d75078] px-6 py-8 text-center text-white sm:px-8 sm:py-10">
                                    <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
                                    <div class="absolute -left-8 -bottom-8 h-40 w-40 rounded-full bg-[#f2b0a6]/20 blur-xl pointer-events-none"></div>
                                    
                                    <div class="relative">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs mb-2">
                                            Paso 1 de 5 &bull; Selección Clínica
                                        </span>
                                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl text-white" x-text="servicioSeleccionado ? 'Elige un tratamiento' : '¿Qué tipo de atención necesitas?'">¿Qué tipo de atención necesitas?</h1>
                                        <p class="mt-2 text-xs sm:text-sm text-white/85 max-w-md mx-auto">Selecciona la especialidad para consultar la agenda disponible de la Dra. Melissa López.</p>
                                        
                                        <!-- Indicadores de paso -->
                                        <div class="mt-5 flex items-center justify-center gap-2">
                                            <template x-for="(p, i) in pasos" :key="i">
                                                <span class="h-2 rounded-full transition-all duration-300" 
                                                      :class="i === paso ? 'w-6 bg-white' : (i < paso ? 'w-2 bg-emerald-400' : 'w-2 bg-white/30')"></span>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 p-6 sm:p-8">
                                    <!-- Si aún no se elige servicio: Cuadrícula de Especialidades -->
                                    <template x-if="!servicioSeleccionado">
                                        <div class="space-y-3">
                                            <template x-for="servicio in servicios" :key="servicio.id">
                                                <button type="button"
                                                        @click="idServicio = servicio.id; idTratamiento = ''"
                                                        class="group flex w-full items-center justify-between rounded-2xl border border-[#e9dfdb] bg-white p-4 sm:p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-400 hover:shadow-md hover:bg-gradient-to-r hover:from-white hover:to-brand-50/20">
                                                    <div class="flex items-center gap-4">
                                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-50 to-peach-100 text-brand-600 border border-brand-200/60 shadow-xs transition group-hover:scale-105 group-hover:bg-brand-600 group-hover:text-white">
                                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h3 class="text-base font-bold text-[#2D2526] group-hover:text-brand-700 transition-colors" x-text="servicio.nombre"></h3>
                                                            <p class="text-xs text-[#8A7A7D] mt-0.5 line-clamp-1" x-text="descripciones[servicio.nombre] || 'Atención personalizada y diagnóstico oportuno'"></p>
                                                        </div>
                                                    </div>
                                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#FAF6F6] text-[#8A7A7D] transition group-hover:bg-brand-600 group-hover:text-white">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Si ya se eligió servicio: Lista de Tratamientos con duraciones -->
                                    <template x-if="servicioSeleccionado">
                                        <div>
                                            <div class="mb-5 flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50/60 p-4">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white font-bold">✓</span>
                                                    <div>
                                                        <p class="text-[11px] font-bold uppercase tracking-wider text-brand-700">Especialidad elegida</p>
                                                        <p class="text-base font-bold text-[#2D2526]" x-text="servicioSeleccionado.nombre"></p>
                                                    </div>
                                                </div>
                                                <button type="button"
                                                        @click="idServicio = ''; idTratamiento = ''"
                                                        class="rounded-xl border border-brand-200 bg-white px-3 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 transition">
                                                    Cambiar
                                                </button>
                                            </div>

                                            <p class="mb-3 text-xs font-bold uppercase tracking-wider text-[#8A7A7D]">Selecciona el procedimiento específico</p>
                                            
                                            <div class="space-y-2.5">
                                                <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                                    <button type="button"
                                                            @click="idTratamiento = tratamiento.id"
                                                            class="group flex w-full items-center justify-between rounded-2xl border p-4 text-left transition-all duration-200"
                                                            :class="Number(idTratamiento) === tratamiento.id 
                                                                ? 'border-brand-500 bg-brand-50/70 text-brand-900 shadow-sm ring-1 ring-brand-400' 
                                                                : 'border-[#e9dfdb] bg-white text-[#2D2526] hover:border-brand-300 hover:bg-[#FAF6F6]'">
                                                        <div class="flex items-center gap-3">
                                                            <span class="flex h-5 w-5 items-center justify-center rounded-full border transition"
                                                                  :class="Number(idTratamiento) === tratamiento.id ? 'border-brand-600 bg-brand-600' : 'border-[#d4c6c9] bg-white'">
                                                                <span class="h-2 w-2 rounded-full bg-white" x-show="Number(idTratamiento) === tratamiento.id"></span>
                                                            </span>
                                                            <span class="font-bold text-sm" x-text="tratamiento.nombre"></span>
                                                        </div>
                                                        <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-[#8A7A7D] border border-[#e9dfdb]">
                                                            <svg class="h-3.5 w-3.5 text-[#8A7A7D]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                            <span x-text="tratamiento.duracion_estimada + ' min'"></span>
                                                        </span>
                                                    </button>
                                                </template>
                                            </div>

                                            <button type="button" :disabled="!idTratamiento" @click="siguiente()"
                                                    class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-6 py-4 text-sm font-bold text-white shadow-soft transition-all duration-200 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 disabled:opacity-40 disabled:pointer-events-none">
                                                <span>Continuar a fecha y horario</span>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 1: SELECCIONAR FECHA -->
                        <template x-if="paso === 1">
                            <div>
                                <div class="relative overflow-hidden bg-gradient-to-br from-[#2D2526] via-[#3a2029] to-[#d75078] px-6 py-8 text-center text-white sm:px-8 sm:py-10">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs mb-2">
                                        Paso 2 de 5 &bull; Calendario
                                    </span>
                                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Seleccionar una fecha</h1>
                                    <p class="mt-2 text-xs sm:text-sm text-white/85 max-w-md mx-auto">Elige el día en el que deseas programar tu valoración en Dentissa.</p>
                                    
                                    <div class="mt-5 flex items-center justify-center gap-2">
                                        <template x-for="(p, i) in pasos" :key="i">
                                            <span class="h-2 rounded-full transition-all duration-300" 
                                                  :class="i === paso ? 'w-6 bg-white' : (i < paso ? 'w-2 bg-emerald-400' : 'w-2 bg-white/30')"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="p-6 sm:p-8 space-y-6">
                                    <div class="rounded-2xl border border-brand-100 bg-[#FAF6F6]/80 p-5">
                                        <label for="fecha-picker" class="block text-xs font-bold uppercase tracking-wider text-brand-700 mb-2">Selecciona el día</label>
                                        <input id="fecha-picker" type="date" :min="new Date().toISOString().split('T')[0]"
                                               x-model="fecha" @change="cargarHorarios()"
                                               class="block w-full rounded-2xl border border-[#e9dfdb] bg-white p-3.5 text-base font-semibold text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                        <p class="mt-2 text-xs text-[#8A7A7D]">Horario regular: Lunes a Viernes de 9:00 a 19:00 hrs &bull; Sábados de 9:00 a 14:00 hrs.</p>
                                    </div>

                                    <div class="flex gap-3">
                                        <button type="button" @click="anterior()" class="w-1/3 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-3.5 text-sm font-bold text-[#51464a] hover:bg-[#FAF6F6] hover:text-brand-700 transition">Atrás</button>
                                        <button type="button" :disabled="!fecha" @click="siguiente()" class="w-2/3 rounded-2xl bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-soft transition hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 disabled:opacity-40">Continuar a horario</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 2: ELEGIR HORA -->
                        <template x-if="paso === 2">
                            <div>
                                <div class="relative overflow-hidden bg-gradient-to-br from-[#2D2526] via-[#3a2029] to-[#d75078] px-6 py-8 text-center text-white sm:px-8 sm:py-10">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs mb-2">
                                        Paso 3 de 5 &bull; Horario
                                    </span>
                                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Elige una hora</h1>
                                    <p class="mt-2 text-xs sm:text-sm text-white/85 max-w-md mx-auto">Turnos confirmados en tiempo real con la agenda del consultorio.</p>
                                    
                                    <div class="mt-5 flex items-center justify-center gap-2">
                                        <template x-for="(p, i) in pasos" :key="i">
                                            <span class="h-2 rounded-full transition-all duration-300" 
                                                  :class="i === paso ? 'w-6 bg-white' : (i < paso ? 'w-2 bg-emerald-400' : 'w-2 bg-white/30')"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="p-6 sm:p-8 space-y-6">
                                    <div class="rounded-2xl border border-[#e9dfdb] bg-white p-5 shadow-xs">
                                        <p class="text-xs font-bold uppercase tracking-wider text-brand-700 mb-3">Horarios disponibles para el día seleccionado</p>
                                        @include('partials.disponibilidad-picker')
                                    </div>

                                    <div class="flex gap-3">
                                        <button type="button" @click="anterior()" class="w-1/3 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-3.5 text-sm font-bold text-[#51464a] hover:bg-[#FAF6F6] hover:text-brand-700 transition">Atrás</button>
                                        <button type="button" :disabled="!hora" @click="siguiente()" class="w-2/3 rounded-2xl bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-soft transition hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 disabled:opacity-40">Revisar resumen</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 3: RESUMEN DE LA CITA -->
                        <template x-if="paso === 3">
                            <div>
                                <div class="relative overflow-hidden bg-gradient-to-br from-[#2D2526] via-[#3a2029] to-[#d75078] px-6 py-8 text-center text-white sm:px-8 sm:py-10">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs mb-2">
                                        Paso 4 de 5 &bull; Confirmación
                                    </span>
                                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Resumen de la cita</h1>
                                    <p class="mt-2 text-xs sm:text-sm text-white/85 max-w-md mx-auto">Revisa el tratamiento, la fecha y la hora antes de continuar</p>
                                    
                                    <div class="mt-5 flex items-center justify-center gap-2">
                                        <template x-for="(p, i) in pasos" :key="i">
                                            <span class="h-2 rounded-full transition-all duration-300" 
                                                  :class="i === paso ? 'w-6 bg-white' : (i < paso ? 'w-2 bg-emerald-400' : 'w-2 bg-white/30')"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="space-y-4 p-6 sm:p-8">
                                    <div class="rounded-2xl border border-brand-200 bg-gradient-to-br from-brand-50/40 via-white to-peach-50/40 p-6 shadow-xs">
                                        <div class="flex items-center gap-3 pb-4 border-b border-brand-100">
                                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-600 text-white font-bold text-lg">
                                                ★
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Consulta en Dentissa</p>
                                                <h3 class="text-lg font-bold text-[#2D2526]" x-text="tratamientoSeleccionado?.nombre"></h3>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                                            <div class="rounded-xl bg-white p-3 border border-[#e9dfdb]">
                                                <p class="text-[11px] font-bold uppercase text-[#8A7A7D]">Especialidad</p>
                                                <p class="text-sm font-bold text-[#2D2526] mt-0.5" x-text="servicioSeleccionado?.nombre"></p>
                                            </div>
                                            <div class="rounded-xl bg-white p-3 border border-[#e9dfdb]">
                                                <p class="text-[11px] font-bold uppercase text-[#8A7A7D]">Fecha y hora</p>
                                                <p class="text-sm font-bold text-brand-700 mt-0.5" x-text="fecha + ' — ' + hora + ' hrs'"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 pt-2">
                                        <button type="button" @click="anterior()" class="w-1/3 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-3.5 text-sm font-bold text-[#51464a] hover:bg-[#FAF6F6] hover:text-brand-700 transition">Atrás</button>
                                        <button type="button" @click="siguiente()" class="w-2/3 rounded-2xl bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-soft transition hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5">Continuar a mis datos</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 4: DATOS DEL PACIENTE -->
                        <template x-if="paso === 4">
                            <div>
                                <div class="relative overflow-hidden bg-gradient-to-br from-[#2D2526] via-[#3a2029] to-[#d75078] px-6 py-8 text-center text-white sm:px-8 sm:py-10">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs mb-2">
                                        Paso 5 de 5 &bull; Datos de Contacto
                                    </span>
                                    <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Datos para agendar</h1>
                                    <p class="mt-2 text-xs sm:text-sm text-white/85 max-w-md mx-auto">Los usamos para confirmar tu cita médica y enviarte recordatorios.</p>
                                    
                                    <div class="mt-5 flex items-center justify-center gap-2">
                                        <template x-for="(p, i) in pasos" :key="i">
                                            <span class="h-2 rounded-full transition-all duration-300" 
                                                  :class="i === paso ? 'w-6 bg-white' : 'w-2 bg-emerald-400'"></span>
                                        </template>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('citas.publica.store') }}" class="space-y-4 p-6 sm:p-8">
                                    @csrf
                                    <input type="hidden" name="id_servicio" :value="idServicio">
                                    <input type="hidden" name="id_tratamiento" :value="idTratamiento">
                                    <input type="hidden" name="fecha" :value="fecha">
                                    <input type="hidden" name="hora" :value="hora">

                                    <div class="rounded-2xl border border-brand-100 bg-[#FAF6F6]/60 p-4 sm:p-5 space-y-4">
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="nombre" class="mb-1 block text-xs font-bold uppercase tracking-wider text-[#382d31]">Nombre *</label>
                                                <input id="nombre" type="text" name="nombre" autocomplete="given-name" maxlength="100" required
                                                       placeholder="Ej. Ana" value="{{ old('nombre') }}"
                                                       aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}"
                                                       @if ($errors->has('nombre')) aria-describedby="nombre-error" @endif
                                                       class="block w-full rounded-xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                                @error('nombre')
                                                    <p id="nombre-error" role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="apellido" class="mb-1 block text-xs font-bold uppercase tracking-wider text-[#382d31]">Apellido *</label>
                                                <input id="apellido" type="text" name="apellido" autocomplete="family-name" maxlength="100" required
                                                       placeholder="Ej. Pérez" value="{{ old('apellido') }}"
                                                       aria-invalid="{{ $errors->has('apellido') ? 'true' : 'false' }}"
                                                       @if ($errors->has('apellido')) aria-describedby="apellido-error" @endif
                                                       class="block w-full rounded-xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                                @error('apellido')
                                                    <p id="apellido-error" role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="telefono" class="mb-1 block text-xs font-bold uppercase tracking-wider text-[#382d31]">Teléfono *</label>
                                                <input id="telefono" type="tel" name="telefono" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[0-9]{10}" title="Ingresa exactamente 10 dígitos numéricos." required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)"
                                                       placeholder="Ej. 5512345678" value="{{ old('telefono') }}"
                                                       aria-invalid="{{ $errors->has('telefono') ? 'true' : 'false' }}"
                                                       @if ($errors->has('telefono')) aria-describedby="telefono-error" @endif
                                                       class="block w-full rounded-xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                                @error('telefono')
                                                    <p id="telefono-error" role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="email" class="mb-1 block text-xs font-bold uppercase tracking-wider text-[#382d31]">Correo electrónico *</label>
                                                <input id="email" type="email" name="email" autocomplete="email" maxlength="100" required
                                                       placeholder="Ej. ana@correo.com" value="{{ old('email') }}"
                                                       aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                                       @if ($errors->has('email')) aria-describedby="email-error" @endif
                                                       class="block w-full rounded-xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">
                                                @error('email')
                                                    <p id="email-error" role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>

                                        <div>
                                            <label for="motivo" class="mb-1 block text-xs font-bold uppercase tracking-wider text-[#382d31]">Motivo de la visita (opcional)</label>
                                            <textarea id="motivo" name="motivo" placeholder="Cuéntanos brevemente si tienes alguna molestia o si es revisión de rutina..." rows="2"
                                                      class="block w-full rounded-xl border border-[#e9dfdb] bg-white p-3 text-sm font-medium text-[#2D2526] shadow-xs focus:border-brand-500 focus:ring-2 focus:ring-brand-200">{{ old('motivo') }}</textarea>
                                        </div>
                                    </div>

                                    @error('hora')
                                        <p class="text-xs text-rose-600 font-medium">{{ $message }}</p>
                                    @enderror

                                    <div class="flex gap-3 pt-2">
                                        <button type="button" @click="anterior()" class="w-1/3 rounded-2xl border border-[#e9dfdb] bg-white px-4 py-3.5 text-sm font-bold text-[#51464a] hover:bg-[#FAF6F6] hover:text-brand-700 transition">Atrás</button>
                                        <button type="submit" class="w-2/3 rounded-2xl bg-brand-600 px-6 py-3.5 text-sm font-bold text-white shadow-soft transition hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5 disabled:opacity-50">Agendar cita</button>
                                    </div>
                                </form>
                            </div>
                        </template>
                    </div>

                    <!-- Ficha Resumen / Ticket Boutique (Columna Derecha) -->
                    <aside data-asistente-zona-reservada class="w-full rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-xl sm:p-7 lg:sticky lg:top-20">
                        <div class="flex items-center justify-between pb-4 border-b border-[#e9dfdb]">
                            <div>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider text-brand-700 border border-brand-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                    Agenda en línea
                                </span>
                                <h2 class="mt-2 text-xl font-bold tracking-tight text-[#2D2526]">Tu cita, paso a paso</h2>
                            </div>
                            <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 flex items-center justify-center text-brand-700 shadow-xs">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-[#6b5e62]">Elige lo que necesitas y revisa la disponibilidad antes de confirmar.</p>

                        <!-- Barra de progreso -->
                        <div class="mt-5 rounded-2xl bg-[#FAF6F6] p-4 border border-[#e9dfdb]/80" role="progressbar" aria-label="Progreso de la cita" aria-valuemin="1" :aria-valuemax="pasos.length" :aria-valuenow="paso + 1">
                            <div class="flex items-center justify-between gap-3 text-xs font-bold">
                                <span class="text-[#8A7A7D]">Paso actual</span>
                                <span class="text-brand-700" x-text="`Paso ${paso + 1} de ${pasos.length}`"></span>
                            </div>
                            <p class="mt-1 text-sm font-bold text-[#2D2526]" x-text="pasos[paso]"></p>
                            <div class="mt-2 flex gap-1" aria-hidden="true">
                                <template x-for="(etapa, indice) in pasos" :key="etapa">
                                    <span class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                          :class="indice <= paso ? 'bg-brand-500' : 'bg-[#e9dfdb]'"></span>
                                </template>
                            </div>
                        </div>

                        <!-- Ticket con datos seleccionados -->
                        <div class="mt-5 rounded-2xl border border-brand-200/80 bg-gradient-to-b from-white to-[#FAF6F6] p-5 shadow-xs" aria-live="polite">
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-700 flex items-center gap-1.5">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Resumen de la cita
                            </p>
                            
                            <dl class="mt-3 divide-y divide-[#e9dfdb]/70 text-xs">
                                <div class="flex justify-between py-2">
                                    <dt class="text-[#8A7A7D] font-medium">Servicio</dt>
                                    <dd class="text-right font-bold text-[#2D2526]" x-text="servicioSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-[#8A7A7D] font-medium">Tratamiento</dt>
                                    <dd class="text-right font-bold text-[#2D2526]" x-text="tratamientoSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-[#8A7A7D] font-medium">Fecha</dt>
                                    <dd class="text-right font-bold text-[#2D2526]" x-text="fecha || 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-[#8A7A7D] font-medium">Hora</dt>
                                    <dd class="text-right font-bold text-brand-700" x-text="hora || 'Pendiente'"></dd>
                                </div>
                            </dl>
                        </div>

                        <p class="mt-4 text-[11px] leading-relaxed text-[#8A7A7D] text-center">Elige un horario disponible y completa tus datos para reservar tu cita.</p>
                    </aside>
                </div>
            </div>
        @endif
    </body>
</html>
