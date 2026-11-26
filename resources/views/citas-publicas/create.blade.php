<!DOCTYPE html>
<html lang="es" class="agenda-publica">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
        <meta name="theme-color" content="#fbf7f3">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.favicon')

        <title>Agendar cita — Dentissa Odontología Integral</title>
        @include('partials.meta-compartir', [
            'titulo' => 'Agenda tu cita — Dentissa | Dra. Melissa López N.',
            'descripcion' => 'Elige el tratamiento, el día y la hora en línea. Te confirmamos por WhatsApp y te recordamos un día antes.',
        ])

        <link rel="preload" href="/fonts/figtree-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-normal.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/fraunces-latin-500-italic.woff2" as="font" type="font/woff2" crossorigin>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-[100dvh] bg-cream font-sans text-ink antialiased">
        <x-asistente-chat />

        @php
            // Clases compartidas del formulario (se definen una vez y se reutilizan).
            $claseCampo = 'block w-full min-h-12 rounded-xl border-field bg-white px-3.5 text-callout text-ink shadow-2xs placeholder:text-muted transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 aria-[invalid=true]:border-rose-400';
            $claseEtiqueta = 'mb-1.5 block text-sm font-semibold text-ink';
            $claseError = 'mt-1.5 text-footnote font-medium text-rose-700';
            $claseAtras = 'btn-secondary flex-1 py-3 sm:flex-none sm:px-6';
            $claseSiguiente = 'btn-brand flex-[2] py-3 sm:flex-none sm:px-8';
            // Fila de botones de cada paso: en el celular queda fija abajo (con el margen
            // de la zona del gesto de inicio); desde 640 px vuelve a su lugar normal.
            $claseBarra = 'sticky bottom-0 z-10 -mx-5 -mb-5 flex gap-3 border-t border-line/80 bg-white/95 px-5 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur supports-[backdrop-filter]:bg-white/85 sm:static sm:mx-0 sm:mb-0 sm:justify-between sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none lg:sticky lg:-bottom-6 lg:-mx-6 lg:-mb-6 lg:border-t lg:bg-white lg:px-6 lg:py-4';
        @endphp

        @if (session('success'))
            <main class="flex min-h-[100dvh] items-center justify-center px-4 py-10">
                <div class="w-full max-w-md rounded-4xl border border-line bg-white p-8 text-center shadow-lift sm:p-10">
                    <div class="conf-circulo mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-sage-50 text-sage-600 ring-8 ring-sage-50/60">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path class="conf-check" pathLength="1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="conf-titulo mt-6 firma text-2xl text-brand-600">Melissa López N.</p>
                    <h1 class="conf-titulo mt-3 text-title-2 text-ink">¡Listo! {{ session('success') }}</h1>
                    @if ($resumen = session('cita_resumen'))
                        <dl class="conf-resumen mt-6 divide-y divide-line/80 rounded-2xl border border-line bg-cream text-left text-sm">
                            <div class="px-5 py-3.5">
                                <dt class="text-xs font-semibold uppercase tracking-label text-muted">Tratamiento</dt>
                                <dd class="mt-1 font-semibold text-ink">{{ $resumen['tratamiento'] }}</dd>
                            </div>
                            <div class="px-5 py-3.5">
                                <dt class="text-xs font-semibold uppercase tracking-label text-muted">Fecha y hora</dt>
                                <dd class="mt-1 font-semibold text-brand-700">{{ ucfirst($resumen['fecha']) }} · {{ $resumen['hora'] }} hrs</dd>
                            </div>
                            @if (! empty($resumen['oferta']))
                                <div class="px-5 py-3.5" data-resumen-oferta>
                                    <dt class="text-xs font-semibold uppercase tracking-label text-muted">Oferta</dt>
                                    <dd class="mt-1 font-semibold text-brand-700">{{ $resumen['oferta'] }}</dd>
                                    <dd class="mt-0.5 text-footnote text-muted">Se aplica en el consultorio el día de tu cita.</dd>
                                </div>
                            @endif
                            @if (! empty($resumen['precio']))
                                <div class="px-5 py-3.5" data-aviso-pago>
                                    <dt class="text-xs font-semibold uppercase tracking-label text-muted">Precio</dt>
                                    <dd class="mt-1 font-semibold text-ink">{{ $resumen['precio'] }}</dd>
                                    <dd class="mt-0.5 text-footnote text-muted">Se paga en el consultorio el día de tu cita.</dd>
                                </div>
                            @endif
                        </dl>
                    @endif
                    <p class="conf-cierre mt-5 text-callout text-muted">Tu cita quedó registrada en el consultorio. Guarda estos datos o toma una captura; si necesitas cambiarla, comunícate con el consultorio.</p>
                    <a href="/" class="conf-cierre btn-brand mt-8 w-full py-3 sm:w-auto sm:px-8">
                        Volver al inicio
                    </a>
                </div>
            </main>
        @else
            <!-- Barra superior -->
            <header class="sticky top-0 z-20 pt-[env(safe-area-inset-top)] border-b border-line/70 bg-cream/90 backdrop-blur-md supports-[backdrop-filter]:bg-cream/75">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
                    <x-marca :href="url('/')" aria-label="Volver al inicio" />

                    <a href="{{ url('/') }}" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-full border border-line bg-white px-3 text-sm sm:px-4 font-semibold text-ink transition-colors hover:border-peach-300 hover:text-brand-700" aria-label="Volver al inicio">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Inicio</span>
                    </a>
                </div>
            </header>

            <main class="pt-6 pb-28 sm:pb-16 sm:pt-6 lg:h-[calc(100dvh-4.0625rem)] lg:py-5"
                 x-data="{
                    paso: {{ $errors->any() ? 3 : ($reagendar ?? null ? 1 : 0) }},
                    pasos: ['Tipo de atención', 'Día', 'Hora', {{ Js::from(($reagendar['sin_datos'] ?? false) ? 'Confirmar' : 'Datos') }}],
                    encabezados: [
                        { etiqueta: 'Selección clínica', titulo: '¿Qué tipo de atención necesitas?', texto: 'Selecciona la especialidad para consultar la agenda disponible de la Dra. Melissa López.' },
                        { etiqueta: 'Calendario', titulo: '¿Qué día te queda mejor?', texto: 'Los días con punto tienen lugar. La agenda se confirma en tiempo real con el consultorio.' },
                        { etiqueta: 'Horario', titulo: '¿A qué hora?', texto: 'Estas son las horas libres del día que elegiste.' },
                        @if ($reagendar['sin_datos'] ?? false)
                        { etiqueta: 'Confirmar', titulo: 'Confirma tu cambio de cita', texto: 'Revisa el nuevo día y la hora. Tus datos son los mismos de tu cita anterior.' },
                        @else
                        { etiqueta: 'Datos de contacto', titulo: 'Datos para agendar', texto: 'Los usamos para confirmar tu cita médica y enviarte recordatorios.' },
                        @endif
                    ],
                    servicios: @js($servicios),
                    idServicio: @js(old('id_servicio', $servicioInicial ?? '')),
                    idTratamiento: @js(old('id_tratamiento', $tratamientoInicial ?? '')),
                    // Ofertas vigentes por tratamiento (21/11/2026). Desde el 23/11/2026 solo
                    // cuenta la que el paciente eligió en la portada (o conserva al reagendar).
                    ofertas: @js((object) ($ofertasPorTratamiento ?? [])),
                    idOferta: @js(old('id_oferta', $ofertaInicial ?? '')),
                    get ofertaTratamiento() {
                        const lista = this.ofertas[Number(this.idTratamiento)] ?? [];
                        return lista.find(o => o.id === Number(this.idOferta)) ?? null;
                    },
                    // Aplica si la cita es dentro de la vigencia (sin fecha elegida todavía, se muestra).
                    get ofertaAplica() {
                        return Boolean(this.ofertaTratamiento && (!this.fecha || this.fecha <= this.ofertaTratamiento.hasta));
                    },
                    fecha: @js(old('fecha', '')),
                    hora: @js(old('hora', null)),
                    // Resumen: 09:00 a 10:00 (las horas del calendario solo muestran el inicio).
                    get horaConFin() {
                        const dur = Number(this.tratamientoSeleccionado?.duracion_estimada) || 0;
                        if (!this.hora || !dur) return this.hora;
                        const t = Number(this.hora.slice(0, 2)) * 60 + Number(this.hora.slice(3, 5)) + dur;
                        return `${this.hora} a ${String(Math.floor(t / 60) % 24).padStart(2, '0')}:${String(t % 60).padStart(2, '0')}`;
                    },
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
                    // Dirección y primera interacción: solo para la animación entre pasos.
                    direccion: 'adelante',
                    interactuado: false,
                    mover(direccion) { this.direccion = direccion; this.interactuado = true; },
                    siguiente() { this.mover('adelante'); this.paso = Math.min(this.paso + 1, this.pasos.length - 1); },
                    anterior() { this.mover('atras'); this.paso = Math.max(this.paso - 1, 0); },
                    formatoPrecio(valor) {
                        const p = Number(valor) || 0;
                        return p > 0 ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: p % 1 ? 2 : 0 }).format(p) : '';
                    },
                    formatoDuracion(minutos) {
                        const m = Number(minutos) || 0;
                        if (m < 60) return `${m} min`;
                        const h = Math.floor(m / 60), resto = m % 60;
                        return resto ? `${h} h ${resto} min` : `${h} h`;
                    },
                    async cargarHorarios() {
                        this.hora = null;
                        if (!this.idTratamiento || !this.fecha) { this.horarios = []; return; }
                        this.loadingHorarios = true;
                        this.horarios = await window.cargarHorariosDisponibles(this.idTratamiento, this.fecha);
                        this.loadingHorarios = false;
                    },
                 }"
                 :class="{ 'pasos-animados': interactuado }"
                 :data-direccion="direccion"
                 x-init="$watch('paso', () => { if ($refs.tarjeta.getBoundingClientRect().top < 0) $refs.tarjeta.scrollIntoView({ block: 'start' }) })"
            >
                <div class="mx-auto grid max-w-6xl grid-cols-1 items-start gap-6 px-4 sm:px-6 lg:h-full lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.75fr)] lg:grid-rows-1 lg:gap-8">

                    <!-- Tarjeta principal con los pasos -->
                    {{-- En PC la tarjeta crece con su contenido hasta el alto de la ventana; si no cabe, se desplaza solo su interior (la barra de botones sigue a la vista). --}}
                    <section x-ref="tarjeta" class="w-full scroll-mt-20 overflow-clip rounded-4xl border border-line bg-white shadow-soft lg:flex lg:max-h-full lg:flex-col">

                        <!-- Encabezado del paso actual (uno solo para los 5 pasos) -->
                        <div class="border-b border-line/80 bg-gradient-to-b from-peach-50 to-white px-5 pb-6 pt-6 sm:px-8 sm:pt-8 lg:shrink-0 lg:pb-4 lg:pt-5">
                            @if ($reagendar ?? null)
                                {{-- Llegó desde "Reagendar" del recordatorio (23/11/2026). --}}
                                <p class="mb-4 rounded-2xl border border-sage-200 bg-sage-50 px-4 py-3 text-footnote text-sage-800 lg:mb-3" role="status" data-reagendar-aviso>
                                    <span class="font-semibold">Cambio de cita: {{ $reagendar['tratamiento'] }}.</span>
                                    Solo elige el nuevo día y la hora; tus datos ya están llenos.
                                </p>
                            @endif
                            <p class="eyebrow flex-wrap gap-x-2 gap-y-1">
                                <span class="whitespace-nowrap" x-text="`Paso ${paso + 1} de ${pasos.length}`">Paso 1 de 5</span>
                                <span aria-hidden="true">·</span>
                                <span class="whitespace-nowrap" x-text="encabezados[paso].etiqueta">Selección clínica</span>
                            </p>
                            <h1 class="mt-3 text-title-2 text-ink lg:mt-2"
                                x-text="paso === 0 && servicioSeleccionado ? 'Elige un tratamiento' : encabezados[paso].titulo">¿Qué tipo de atención necesitas?</h1>
                            <p class="mt-2 max-w-lg text-callout text-muted lg:[@media(max-height:820px)]:hidden" x-text="paso === 0 && servicioSeleccionado ? 'Selecciona el procedimiento que necesitas; cada uno muestra su duración aproximada.' : encabezados[paso].texto">Selecciona la especialidad para consultar la agenda disponible de la Dra. Melissa López.</p>

                            <div class="mt-5 flex gap-1.5 lg:mt-4" aria-hidden="true">
                                <template x-for="(p, i) in pasos" :key="i">
                                    <span class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                          :class="i < paso ? 'bg-sage-500' : (i === paso ? 'bg-brand-500' : 'bg-line')"></span>
                                </template>
                            </div>
                        </div>

                        <!-- PASO 0: SERVICIO Y TRATAMIENTO -->
                        <template x-if="paso === 0">
                            <div class="p-5 sm:p-8 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:p-6">
                                <!-- Especialidades -->
                                <template x-if="!servicioSeleccionado">
                                    <div class="paso-anim grid gap-3">
                                        <template x-for="servicio in servicios" :key="servicio.id">
                                            <button type="button"
                                                    @click="mover('adelante'); idServicio = servicio.id; idTratamiento = ''"
                                                    class="group flex w-full items-center gap-4 rounded-2xl border border-line bg-white p-4 text-left transition-[border-color,background-color,transform] duration-150 ease-out hover:border-peach-300 hover:bg-peach-50/50 active:scale-[0.99] sm:p-5">
                                                <span class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200 transition-colors group-hover:bg-white sm:flex">
                                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block font-serif text-title-3 text-ink" x-text="servicio.nombre"></span>
                                                    <span class="mt-0.5 block text-sm leading-5 text-muted line-clamp-2" x-text="descripciones[servicio.nombre] || 'Atención personalizada y diagnóstico oportuno'"></span>
                                                </span>
                                                <svg class="h-5 w-5 shrink-0 text-muted transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Tratamientos de la especialidad elegida -->
                                <template x-if="servicioSeleccionado">
                                    <div class="paso-anim">
                                        <div class="mb-6 flex items-center justify-between gap-3 rounded-2xl bg-cream p-3 pl-4 ring-1 ring-inset ring-line">
                                            <div class="min-w-0">
                                                <p class="text-xs font-semibold uppercase tracking-label text-muted">Especialidad elegida</p>
                                                <p class="font-serif text-title-3 text-ink" x-text="servicioSeleccionado.nombre"></p>
                                            </div>
                                            <button type="button"
                                                    @click="mover('atras'); idServicio = ''; idTratamiento = ''"
                                                    class="inline-flex min-h-11 shrink-0 items-center rounded-full bg-white px-4 text-sm font-semibold text-brand-700 ring-1 ring-inset ring-line transition-colors hover:ring-peach-300">
                                                Cambiar
                                            </button>
                                        </div>

                                        <p id="titulo-tratamientos" x-show="servicioSeleccionado.tratamientos.length > 0" class="mb-3 text-sm font-semibold text-ink">Selecciona el procedimiento específico</p>

                                        <div class="grid gap-2.5" role="radiogroup" aria-labelledby="titulo-tratamientos">
                                            <template x-for="tratamiento in servicioSeleccionado.tratamientos" :key="tratamiento.id">
                                                <button type="button"
                                                        role="radio"
                                                        :aria-checked="(Number(idTratamiento) === tratamiento.id).toString()"
                                                        @click="if (idTratamiento !== tratamiento.id) { fecha = ''; hora = null; horarios = []; } idTratamiento = tratamiento.id"
                                                        class="flex w-full items-start justify-between gap-3 rounded-2xl border p-4 text-left transition-[border-color,background-color] duration-150"
                                                        :class="Number(idTratamiento) === tratamiento.id
                                                            ? 'border-brand-400 bg-brand-50/60 ring-1 ring-brand-400'
                                                            : 'border-line bg-white hover:border-peach-300'">
                                                    <span class="flex min-w-0 items-start gap-3">
                                                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                                                              :class="Number(idTratamiento) === tratamiento.id ? 'border-brand-600 bg-brand-600' : 'border-line bg-white'">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-white" x-show="Number(idTratamiento) === tratamiento.id"
                                                                  x-transition:enter="transition-[opacity,transform] duration-[120ms] ease-out" x-transition:enter-start="opacity-0 scale-50 motion-reduce:scale-100" x-transition:enter-end="opacity-100 scale-100"></span>
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="block text-callout font-semibold text-ink" x-text="tratamiento.nombre"></span>
                                                            <span x-show="(ofertas[tratamiento.id] ?? []).some(o => o.id === Number(idOferta))" class="mt-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-800 ring-1 ring-inset ring-brand-200" data-etiqueta-oferta
                                                                  x-text="(ofertas[tratamiento.id] ?? []).some(o => o.id === Number(idOferta)) ? `Oferta ${ofertas[tratamiento.id].find(o => o.id === Number(idOferta)).etiqueta}` : ''"></span>
                                                            <span class="mt-0.5 block text-footnote text-muted sm:hidden" x-text="[formatoDuracion(tratamiento.duracion_estimada), formatoPrecio(tratamiento.precio_referencia)].filter(Boolean).join(' · ')"></span>
                                                        </span>
                                                    </span>
                                                    <span class="mt-0.5 hidden shrink-0 items-center gap-1 whitespace-nowrap text-xs font-medium text-muted sm:inline-flex">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        <span x-text="formatoDuracion(tratamiento.duracion_estimada)"></span>
                                                        <span class="ml-2 text-callout font-semibold text-ink" data-precio-tratamiento x-text="formatoPrecio(tratamiento.precio_referencia)"></span>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>

                                        <p x-show="ofertaTratamiento" x-cloak class="mt-3 rounded-xl border border-brand-200 bg-brand-50 px-3.5 py-2.5 text-footnote text-brand-800" data-aviso-oferta role="status">
                                            <span class="font-semibold" x-text="ofertaTratamiento?.titulo"></span>:
                                            <span x-text="ofertaTratamiento ? `${ofertaTratamiento.etiqueta} en citas hasta el ${ofertaTratamiento.hasta_legible}. Se aplica en el consultorio.` : ''"></span>
                                        </p>

                                        <p x-show="servicioSeleccionado.tratamientos.length > 0" data-aviso-pago class="mt-3 flex items-center gap-2 text-footnote text-muted">
                                            <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                                            El pago se realiza en el consultorio el día de tu cita.
                                        </p>

                                        <template x-if="servicioSeleccionado.tratamientos.length === 0">
                                            <div class="rounded-2xl bg-cream px-4 py-5 text-center">
                                                <p class="text-callout text-ink-soft">Por ahora no hay procedimientos para agendar en línea en esta especialidad.</p>
                                                <button type="button" @click="mover('atras'); idServicio = ''; idTratamiento = ''" class="btn-secondary mt-3">Elegir otra especialidad</button>
                                            </div>
                                        </template>

                                        <p x-show="!idTratamiento && servicioSeleccionado.tratamientos.length > 0" class="mt-5 text-footnote text-muted sm:text-right" aria-live="polite">Elige un procedimiento para continuar.</p>
                                        <div class="{{ $claseBarra }} mt-3">
                                            <button type="button" :disabled="!idTratamiento" @click="siguiente()" class="btn-brand w-full py-3 sm:ml-auto sm:w-auto sm:px-8">
                                                <span>Continuar a fecha y horario</span>
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- PASO 1: DÍA (solo el mes; tocar otra vez el día lo quita) -->
                        <template x-if="paso === 1">
                            <div class="paso-anim space-y-6 p-5 sm:p-8 lg:min-h-0 lg:flex-1 lg:space-y-5 lg:overflow-y-auto lg:p-6">
                                <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_13rem] lg:items-center lg:gap-8">
                                    @include('partials.disponibilidad-picker', ['vista' => 'dia'])
                                    <div class="mt-4 rounded-2xl bg-cream px-4 py-3 text-center text-footnote text-muted lg:mt-0 lg:text-left">
                                        <p class="font-semibold text-ink-soft">Horario del consultorio</p>
                                        @foreach (\App\Models\HorarioAtencion::lineasSemanales() as $linea)
                                            <p class="mt-1">{{ $linea }}</p>
                                        @endforeach
                                        <p class="mt-2 hidden lg:block">Toca otra vez el día elegido para quitarlo.</p>
                                    </div>
                                </div>

                                <div class="{{ $claseBarra }}">
                                    {{-- Al reagendar no se regresa a elegir tratamiento: solo cambian día y hora. --}}
                                    @unless ($reagendar ?? null)
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    @endunless
                                    {{-- El aviso va en la barra (no en un renglón aparte) para no obligar a desplazarse. --}}
                                    <p x-show="!fecha" class="hidden self-center text-footnote text-muted sm:ml-auto sm:block" aria-live="polite">Elige un día para continuar.</p>
                                    <button type="button" :disabled="!fecha" @click="siguiente()" class="{{ $claseSiguiente }}">Elegir hora</button>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 2: HORA (horas libres del día elegido, en cuadrícula) -->
                        <template x-if="paso === 2">
                            <div class="paso-anim space-y-6 p-5 sm:p-8 lg:min-h-0 lg:flex-1 lg:space-y-5 lg:overflow-y-auto lg:p-6">
                                @include('partials.disponibilidad-picker', ['vista' => 'horas'])

                                <div class="{{ $claseBarra }}">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Cambiar día</button>
                                    <p x-show="!hora" class="hidden self-center text-footnote text-muted sm:ml-auto sm:block" aria-live="polite">Elige un horario para continuar.</p>
                                    <button type="button" :disabled="!hora" @click="siguiente()" class="{{ $claseSiguiente }}">{{ ($reagendar ?? null) ? 'Continuar' : 'Continuar a mis datos' }}</button>
                                </div>
                            </div>
                        </template>

                        <!-- PASO 3: DATOS DEL PACIENTE -->
                        <template x-if="paso === 3">
                            @php
                                // Errores del servidor (si el envío volvió con errores) para el componente.
                                $erroresServidor = collect(['nombre', 'apellido', 'telefono', 'email', 'acepta_aviso'])
                                    ->filter(fn ($campo) => $errors->has($campo))
                                    ->mapWithKeys(fn ($campo) => [$campo === 'acepta_aviso' ? 'aviso' : $campo => $errors->first($campo)])
                                    ->all();
                                $camposConError = count($erroresServidor);
                                $datosIniciales = [
                                    'nombre' => old('nombre', $reagendar['datos']['nombre'] ?? ''),
                                    'apellido' => old('apellido', $reagendar['datos']['apellido'] ?? ''),
                                    'telefono' => old('telefono', $reagendar['datos']['telefono'] ?? ''),
                                    'email' => old('email', $reagendar['datos']['email'] ?? ''),
                                    'aviso' => (bool) old('acepta_aviso') || (($reagendar['sin_datos'] ?? false) && ! $reagendar['pide_aviso']),
                                    'errores' => (object) $erroresServidor,
                                ];
                                // Palomita verde dentro del campo cuando el dato ya está bien.
                                $palomita = '<span aria-hidden="true" class="pointer-events-none absolute right-3 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-sage-50 text-xs font-bold text-sage-600">✓</span>';
                            @endphp
                            <form method="POST" action="{{ route('citas.publica.store') }}" class="paso-anim space-y-5 p-5 sm:p-8 lg:min-h-0 lg:flex-1 lg:space-y-4 lg:overflow-y-auto lg:p-6" novalidate
                                  x-data="datosPaciente(@js($datosIniciales))"
                                  x-on:submit="enviar($event)"
                                  x-init="$nextTick(() => $el.querySelector('[aria-invalid=true]')?.focus())">
                                @csrf
                                @if ($reagendar['token'] ?? null)
                                    <input type="hidden" name="reagendar" value="{{ $reagendar['token'] }}">
                                @endif

                                {{-- Recordatorio de la cita que se está agendando; "Cambiar" regresa a fecha y hora. --}}
                                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 rounded-2xl border border-line bg-cream px-4 py-3 text-sm text-ink-soft">
                                    <span>
                                        <span class="font-semibold text-ink" x-text="tratamientoSeleccionado?.nombre"></span>
                                        · <span x-text="fechaLegible"></span>
                                        · <span class="font-semibold tabular-nums text-ink" x-text="hora ? `${hora} hrs` : ''"></span>
                                        <span x-show="ofertaAplica" x-cloak class="font-semibold text-brand-700" x-text="ofertaAplica ? `· Oferta ${ofertaTratamiento.etiqueta}` : ''"></span>
                                    </span>
                                    <button type="button" @click="mover('atras'); paso = 1" class="link inline-flex min-h-11 items-center text-footnote">Cambiar</button>
                                </div>

                                <div x-show="intentado && cuentaErrores > 0" role="alert" @if ($camposConError === 0) style="display: none" @endif>
                                    <x-alert type="error">
                                        <span x-text="cuentaErrores === 1 ? 'Revisa el dato marcado para poder agendar tu cita.' : `Revisa los ${cuentaErrores} datos marcados para poder agendar tu cita.`">{{ $camposConError === 1 ? 'Revisa el dato marcado para poder agendar tu cita.' : "Revisa los {$camposConError} datos marcados para poder agendar tu cita." }}</span>
                                    </x-alert>
                                </div>
                                <input type="hidden" name="id_servicio" :value="idServicio">
                                <input type="hidden" name="id_tratamiento" :value="idTratamiento">
                                <input type="hidden" name="fecha" :value="fecha">
                                <input type="hidden" name="hora" :value="hora">
                                <input type="hidden" name="id_oferta" :value="ofertaAplica ? ofertaTratamiento.id : ''">

                                @if ($reagendar['sin_datos'] ?? false)
                                    {{-- Reagendar (23/11/2026): los datos no se vuelven a escribir; van ocultos. --}}
                                    @foreach (['nombre', 'apellido', 'telefono', 'email'] as $campo)
                                        <input type="hidden" name="{{ $campo }}" value="{{ $reagendar['datos'][$campo] ?? '' }}">
                                    @endforeach
                                    <div class="rounded-2xl border border-line bg-white px-4 py-3 text-sm text-ink-soft" data-datos-reagendar>
                                        <p class="text-xs font-semibold uppercase tracking-label text-muted">Tus datos</p>
                                        <p class="mt-1 font-semibold text-ink">{{ $reagendar['datos']['nombre'] }} {{ $reagendar['datos']['apellido'] }}</p>
                                        <p class="tabular-nums" x-text="valores.telefono"></p>
                                        <p class="mt-2 text-footnote text-muted">¿Cambió tu número? Llama al consultorio al <a class="link" href="{{ config('dentissa.contacto.telefono_enlace') }}">{{ config('dentissa.contacto.telefono') }}</a>.</p>
                                    </div>
                                @else
                                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    @foreach ([
                                        ['nombre', 'Nombre', 'text', 'given-name', 'text', 'Ej. Ana', 100],
                                        ['apellido', 'Apellido', 'text', 'family-name', 'text', 'Ej. Pérez', 100],
                                        ['telefono', 'Teléfono (WhatsApp)', 'tel', 'tel-national', 'numeric', '755 123 4567', 12],
                                        ['email', 'Correo electrónico', 'email', 'email', 'email', 'Ej. ana@correo.com', 100], // opcional
                                    ] as [$campo, $etiqueta, $tipo, $autocompletar, $teclado, $ejemplo, $maximo])
                                        <div>
                                            <label for="{{ $campo }}" class="{{ $claseEtiqueta }}">{{ $etiqueta }} @if ($campo === 'email')<span class="font-normal text-muted">(opcional)</span>@else<span class="text-brand-600" aria-hidden="true">*</span>@endif</label>
                                            <div class="relative">
                                                <input id="{{ $campo }}" enterkeyhint="next" type="{{ $tipo }}" name="{{ $campo }}" autocomplete="{{ $autocompletar }}" inputmode="{{ $teclado }}" maxlength="{{ $maximo }}" @required($campo !== 'email')
                                                       placeholder="{{ $ejemplo }}" value="{{ old($campo, $reagendar['datos'][$campo] ?? '') }}"
                                                       @if ($campo === 'telefono')
                                                           :value="valores.telefono" x-on:input="escribirTelefono($event)"
                                                       @else
                                                           x-model="valores.{{ $campo }}" x-on:input="alEscribir('{{ $campo }}')"
                                                       @endif
                                                       x-on:blur="alSalir('{{ $campo }}')"
                                                       aria-invalid="{{ $errors->has($campo) ? 'true' : 'false' }}" :aria-invalid="(!!errores.{{ $campo }}).toString()"
                                                       aria-describedby="{{ $campo }}-error"
                                                       class="{{ $claseCampo }} pr-10">
                                                <template x-if="bien('{{ $campo }}')">{!! $palomita !!}</template>
                                            </div>
                                            <p id="{{ $campo }}-error" role="alert" class="{{ $claseError }}" x-show="errores.{{ $campo }}" x-text="errores.{{ $campo }}"
                                               @unless ($errors->has($campo)) style="display: none" @endunless>{{ $errors->first($campo) }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                @endif

                                {{-- "Motivo de la visita" se quitó (20/11/2026): se platica en la consulta. El servidor lo sigue aceptando como opcional. --}}

                                {{-- Consentimiento expreso: el motivo puede contener datos de salud. Al reagendar
                                     solo se pide si el paciente nunca lo aceptó. --}}
                                @if (! ($reagendar['sin_datos'] ?? false) || $reagendar['pide_aviso'])
                                <div>
                                    <label for="acepta_aviso" class="flex min-h-11 cursor-pointer items-start gap-3 rounded-xl border bg-cream/60 px-4 py-3 text-callout text-ink-soft transition-colors"
                                           :class="errores.aviso ? 'border-rose-400' : 'border-line'">
                                        <input id="acepta_aviso" type="checkbox" name="acepta_aviso" value="1" required
                                               @checked(old('acepta_aviso'))
                                               x-model="valores.aviso" x-on:change="validar('aviso')"
                                               aria-invalid="{{ $errors->has('acepta_aviso') ? 'true' : 'false' }}" :aria-invalid="(!!errores.aviso).toString()"
                                               aria-describedby="acepta_aviso-error"
                                               class="mt-1 h-5 w-5 shrink-0 rounded border-line text-brand-600 focus:ring-brand-500">
                                        <span>
                                            He leído y acepto el <a href="{{ route('privacidad.aviso') }}" target="_blank" rel="noopener" class="link">aviso de privacidad</a>, y doy mi consentimiento para el tratamiento de mis datos, incluidos los de salud. <span class="text-brand-600" aria-hidden="true">*</span>
                                        </span>
                                    </label>
                                    <p id="acepta_aviso-error" role="alert" class="{{ $claseError }}" x-show="errores.aviso" x-text="errores.aviso"
                                       @unless ($errors->has('acepta_aviso')) style="display: none" @endunless>{{ $errors->first('acepta_aviso') }}</p>
                                </div>
                                @endif

                                @error('hora')
                                    <x-alert type="error" :message="$message" />
                                @enderror

                                @error('envios')
                                    <x-alert type="warning" :message="$message" />
                                @enderror

                                <div class="{{ $claseBarra }}">
                                    <button type="button" @click="anterior()" class="{{ $claseAtras }}">Atrás</button>
                                    <button type="submit" class="{{ $claseSiguiente }}" data-loading-text="Agendando…"><span data-loading-label>{{ ($reagendar['sin_datos'] ?? false) ? 'Confirmar cambio de cita' : 'Agendar cita' }}</span></button>
                                </div>
                            </form>
                        </template>
                    </section>

                    <!-- Resumen lateral (ticket) -->
                    <aside class="w-full self-start rounded-4xl border border-line bg-white p-5 shadow-soft sm:p-7 lg:max-h-full lg:overflow-y-auto lg:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="eyebrow">Agenda en línea</p>
                                <h2 class="mt-2 text-title-3 text-ink">Tu cita, paso a paso</h2>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>

                        <p class="mt-2 text-callout text-muted lg:[@media(max-height:820px)]:hidden">Elige lo que necesitas y revisa la disponibilidad antes de confirmar.</p>

                        <!-- Progreso -->
                        <div class="sr-only" role="progressbar" aria-label="Progreso de la cita" aria-valuemin="1" :aria-valuemax="pasos.length" :aria-valuenow="paso + 1">
                            <div class="flex items-center justify-between gap-3 text-xs font-semibold">
                                <span class="text-muted">Paso actual</span>
                                <span class="text-brand-700" x-text="`Paso ${paso + 1} de ${pasos.length}`"></span>
                            </div>
                            <p class="mt-1 text-sm font-semibold text-ink" x-text="pasos[paso]"></p>
                        </div>

                        <!-- Datos seleccionados -->
                        <div class="mt-5 rounded-2xl border border-dashed border-peach-300 p-4" aria-live="polite">
                            <p class="text-xs font-semibold uppercase tracking-label text-brand-700">Resumen de la cita</p>
                            <dl class="mt-2 divide-y divide-line/80 text-sm">
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Servicio</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!servicioSeleccionado && 'font-normal text-muted'" x-text="servicioSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Tratamiento</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!tratamientoSeleccionado && 'font-normal text-muted'" x-text="tratamientoSeleccionado?.nombre ?? 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5" x-show="tratamientoSeleccionado && formatoPrecio(tratamientoSeleccionado.precio_referencia)">
                                    <dt class="shrink-0 text-muted">Precio</dt>
                                    <dd class="min-w-0 text-right">
                                        <span class="block font-semibold text-ink" x-text="tratamientoSeleccionado ? formatoPrecio(tratamientoSeleccionado.precio_referencia) : ''"></span>
                                        <span class="block text-xs text-muted">Se paga en el consultorio</span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5" x-show="ofertaTratamiento" x-cloak data-resumen-oferta>
                                    <dt class="shrink-0 text-muted">Oferta</dt>
                                    <dd class="min-w-0 text-right">
                                        <span class="block font-semibold" :class="ofertaAplica ? 'text-brand-700' : 'text-muted line-through'" x-text="ofertaTratamiento?.etiqueta"></span>
                                        <span class="block text-xs text-muted" x-text="ofertaAplica ? 'Se aplica en el consultorio' : `Solo en citas hasta el ${ofertaTratamiento?.hasta_legible}`"></span>
                                    </dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Fecha</dt>
                                    <dd class="min-w-0 text-right font-semibold text-ink" :class="!fecha && 'font-normal text-muted'" x-text="fechaLegible || 'Pendiente'"></dd>
                                </div>
                                <div class="flex justify-between gap-4 py-2.5">
                                    <dt class="shrink-0 text-muted">Hora</dt>
                                    <dd class="min-w-0 text-right font-semibold text-brand-700" :class="!hora && 'font-normal text-muted'" x-text="horaConFin || 'Pendiente'"></dd>
                                </div>
                            </dl>
                        </div>

                        <p class="mt-4 text-center text-footnote text-muted lg:[@media(max-height:820px)]:hidden">Elige un horario disponible y completa tus datos para reservar tu cita.</p>
                    </aside>
                </div>
            </main>
        @endif
    </body>
</html>
