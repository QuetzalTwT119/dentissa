<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Agendar cita</title>

        @include('partials.pwa-meta')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=alex-brush:400&display=block" rel="stylesheet" />

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
            <div class="min-h-screen bg-brand-50 pb-16"
                 x-data="{
                    paso: {{ $errors->any() ? 4 : 0 }},
                    pasos: ['Tipo de atención', 'Fecha', 'Hora', 'Confirmar', 'Tus datos'],
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
                <div class="mx-auto max-w-lg px-6 pt-6 sm:px-0">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('atencion.urgencia') }}" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900 transition hover:bg-red-100">
                            <strong class="block">¿Dolor intenso o urgencia?</strong>
                            <span class="mt-1 block">Pide que el consultorio te contacte.</span>
                        </a>
                        <a href="{{ route('atencion.lista-espera') }}" class="rounded-lg border border-brand-200 bg-white p-4 text-sm text-brand-900 transition hover:bg-brand-50">
                            <strong class="block">¿No encuentras horario?</strong>
                            <span class="mt-1 block">Únete a la lista de espera.</span>
                        </a>
                    </div>
                </div>
                <div class="mx-auto max-w-lg overflow-hidden bg-white shadow-lg sm:mt-10 sm:rounded-xl">
                    <template x-if="paso === 0">
                        <div>
                            <div class="bg-brand-500 px-6 py-8 text-center text-white">
                                <h1 class="text-2xl font-semibold sm:text-3xl">Seleccione su tipo de atención</h1>
                                <div class="mt-4 flex items-center justify-center gap-2">
                                    <template x-for="(p, i) in pasos" :key="i">
                                        <span class="h-2 w-2 rounded-full transition" :class="i === paso ? 'bg-white' : 'bg-white/40'"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="space-y-3 p-6">
                                <template x-for="servicio in servicios" :key="servicio.id">
                                    <button type="button"
                                            @click="idServicio = servicio.id; idTratamiento = ''"
                                            class="block w-full rounded-lg border px-4 py-3 text-left font-medium transition"
                                            :class="Number(idServicio) === servicio.id ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-200 text-gray-700 hover:border-brand-200'"
                                            x-text="servicio.nombre"></button>
                                </template>

                                <template x-if="servicioSeleccionado">
                                    <div class="pt-2">
                                        <p class="mb-2 text-sm font-medium text-gray-600">Tratamiento</p>
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
                                <h1 class="text-2xl font-semibold sm:text-3xl">Seleccione una hora</h1>
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
                                <h1 class="text-2xl font-semibold sm:text-3xl">Confirmación de datos de la cita</h1>
                                <p class="mt-2 text-sm text-brand-50">Revisa que todo esté correcto</p>
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
                                    <button type="button" @click="siguiente()" class="w-full rounded-md bg-brand-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">Confirmar</button>
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

                                <div>
                                    <input type="text" name="nombre" placeholder="Nombre" value="{{ old('nombre') }}"
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('nombre')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <input type="text" name="apellido" placeholder="Apellido" value="{{ old('apellido') }}"
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('apellido')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <input type="tel" name="telefono" placeholder="Teléfono" value="{{ old('telefono') }}"
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('telefono')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <input type="email" name="email" placeholder="Correo" value="{{ old('email') }}"
                                           class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                    @error('email')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <textarea name="motivo" placeholder="Motivo de la visita (opcional)" rows="2"
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
            </div>

            <div class="mx-auto mt-6 max-w-lg px-6 sm:px-0"
                 x-data="{
                    consulta: '',
                    conversacionId: localStorage.getItem('dentissa_asistente_conversacion') ?? crypto.randomUUID(),
                    mensajes: [{ autor: 'bot', texto: 'Hola, soy Dentissa Asistente. Puedo responder preguntas aprobadas o ayudarte a buscar horarios. Escribe, por ejemplo: Quiero una cita.' }],
                    cargando: false,
                    init() { localStorage.setItem('dentissa_asistente_conversacion', this.conversacionId); },
                    async preguntar() {
                        if (this.consulta.trim().length < 3) return;
                        const texto = this.consulta.trim();
                        this.mensajes.push({ autor: 'persona', texto });
                        this.consulta = '';
                        this.cargando = true;
                        try {
                            const respuesta = await fetch('{{ route('asistente-paciente.responder') }}', {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ consulta: texto, conversacion_id: this.conversacionId }),
                            });
                            const datos = await respuesta.json();
                            this.mensajes.push({ autor: 'bot', texto: datos.respuesta ?? 'No fue posible responder en este momento.', accion: datos.accion ?? null });
                        } catch (error) {
                            this.mensajes.push({ autor: 'bot', texto: 'No fue posible responder en este momento. Intenta nuevamente o comunícate con el consultorio.' });
                        } finally {
                            this.cargando = false;
                            this.$nextTick(() => this.$refs.historial.scrollTop = this.$refs.historial.scrollHeight);
                        }
                    },
                 }">
                <div class="rounded-xl bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Dentissa Asistente</h2>
                    <p class="mt-1 text-sm text-gray-600">El mismo asistente de Telegram, ahora también en esta página.</p>
                    <div x-ref="historial" class="mt-4 max-h-72 space-y-3 overflow-y-auto rounded-lg bg-gray-50 p-3">
                        <template x-for="(mensaje, indice) in mensajes" :key="indice">
                            <div class="flex" :class="mensaje.autor === 'persona' ? 'justify-end' : 'justify-start'">
                                <p class="max-w-[88%] whitespace-pre-line rounded-lg px-3 py-2 text-sm" :class="mensaje.autor === 'persona' ? 'bg-brand-500 text-white' : 'bg-white text-gray-800 shadow-sm'" x-text="mensaje.texto"></p>
                                <a x-show="mensaje.accion" :href="mensaje.accion?.url" class="mt-2 inline-block rounded-md bg-brand-500 px-3 py-2 text-xs font-semibold text-white hover:bg-brand-600" x-text="mensaje.accion?.etiqueta"></a>
                            </div>
                        </template>
                        <p x-show="cargando" class="text-sm text-gray-500">Dentissa Asistente está respondiendo…</p>
                    </div>
                    <form class="mt-4 space-y-3" @submit.prevent="preguntar()">
                        <label for="consulta-asistente" class="sr-only">Escribe tu pregunta</label>
                        <input id="consulta-asistente" type="text" x-model="consulta" placeholder="Ej. Quiero una cita o ¿Cuánto cuesta una limpieza?"
                               class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500">
                        <button type="submit" :disabled="cargando || consulta.trim().length < 3"
                                class="rounded-md bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:opacity-50">
                            Enviar
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </body>
</html>
