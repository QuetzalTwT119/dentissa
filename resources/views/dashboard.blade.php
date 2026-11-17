@php
    $usuario = auth()->user();
    $esAdmin = $usuario->rol === \App\Enums\RolUsuario::Admin;

    // Saludo según la hora local del consultorio.
    $hora = (int) now()->format('G');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $fechaHoy = ucfirst(now()->locale('es')->translatedFormat('l j \d\e F'));

    $accesos = [
        [
            'url' => route('citas.create'),
            'titulo' => 'Agendar cita',
            'texto' => 'Registra una nueva cita para un paciente.',
            'accion' => 'Abrir agenda',
            'icono' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'url' => route('admin.pacientes.index'),
            'titulo' => 'Pacientes',
            'texto' => 'Consulta expedientes y datos de contacto.',
            'accion' => 'Ver directorio médico',
            'icono' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        ],
        [
            'url' => route('admin.servicios.index'),
            'titulo' => 'Servicios',
            'texto' => 'Actualiza el catálogo de la clínica.',
            'accion' => 'Gestionar catálogo',
            'icono' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        ],
        [
            'url' => route('admin.casos.index'),
            'titulo' => 'Contenido clínico',
            'texto' => 'Administra casos, galería y testimonios.',
            'accion' => 'Ver publicaciones',
            'icono' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
        ],
        [
            'url' => route('admin.usuarios.index'),
            'titulo' => 'Usuarios',
            'texto' => 'Gestiona accesos y permisos del equipo.',
            'accion' => 'Gestionar equipo',
            'icono' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        ],
        [
            'url' => route('admin.horarios.edit'),
            'titulo' => 'Horarios',
            'texto' => 'Define los turnos y días de atención.',
            'accion' => 'Configurar horarios',
            'icono' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    ];
@endphp

<x-app-layout>
    @if (! $esAdmin)
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-16 lg:px-8">
            <div class="relative overflow-hidden rounded-4xl border border-line bg-white px-6 py-10 text-center shadow-soft sm:px-12 sm:py-14">
                <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-peach-100/70 blur-3xl"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-brand-50 blur-3xl"></div>

                <div class="relative">
                    <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N. Odontología integral" class="mx-auto h-20 w-auto object-contain">

                    <p class="eyebrow mt-6 justify-center">Portal de Paciente &bull; Dentissa</p>
                    <p class="mt-4 text-sm text-muted">Hola, {{ $usuario->nombre }}.</p>
                    <h1 class="mt-1 text-title-1 text-ink">Tu espacio de atención</h1>
                    <p class="mx-auto mt-3 max-w-md text-body text-ink-soft">Consulta tus citas y continúa con el asistente de agenda cuando lo necesites.</p>

                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('citas.index') }}" class="btn-brand px-7 py-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Ver mis citas
                        </a>
                        <a href="{{ route('asistente-agenda.create') }}" class="btn-secondary px-7 py-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 text-brand-600" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Abrir asistente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl space-y-10 px-4 py-8 sm:px-6 lg:px-8 lg:py-12">

            <!-- Bienvenida -->
            <section class="relative overflow-hidden rounded-4xl bg-gradient-to-br from-peach-100 via-peach-50 to-white p-6 ring-1 ring-inset ring-peach-200/70 sm:p-10">
                <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-brand-200/40 blur-3xl"></div>

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex items-start gap-4 sm:items-center sm:gap-5">
                        @if ($usuario->foto_perfil)
                            <img src="{{ route('profile.photo.show') }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-4 ring-white sm:h-20 sm:w-20">
                        @else
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white font-serif text-2xl text-brand-700 shadow-soft ring-4 ring-white/70 sm:h-20 sm:w-20 sm:text-3xl" aria-hidden="true">
                                {{ \App\Support\Iniciales::de($usuario->nombre, $usuario->apellido) }}
                            </span>
                        @endif

                        <div class="min-w-0">
                            <p class="eyebrow">Panel de administración</p>
                            <h1 class="mt-2 text-title-1 text-ink">{{ $saludo }}, {{ $usuario->nombre }}.</h1>
                            <p class="mt-1 text-sm text-ink-soft">{{ $fechaHoy }} · Gestiona la agenda, pacientes y contenido de la clínica desde un solo lugar.</p>
                        </div>
                    </div>

                    <a href="{{ route('citas.create') }}" class="btn-brand shrink-0 px-6 py-3">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5" /></svg>
                        <span>Nueva cita</span>
                    </a>
                </div>

                <!-- Atajos de la jornada -->
                <div class="relative mt-8 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach ([
                        [route('citas.index'), 'Agenda activa', 'Consultas de hoy'],
                        [route('admin.pacientes.index'), 'Expedientes', 'Directorio oficial'],
                        [route('admin.horarios.edit'), 'Horarios', 'Turnos configurados'],
                    ] as [$url, $etiqueta, $texto])
                        <a href="{{ $url }}" class="group flex min-h-16 items-center justify-between gap-3 rounded-2xl bg-white/80 px-4 py-3 ring-1 ring-inset ring-white transition-colors duration-150 hover:bg-white">
                            <span>
                                <span class="block text-caption font-semibold uppercase tracking-label text-brand-700">{{ $etiqueta }}</span>
                                <span class="mt-0.5 block text-sm font-semibold text-ink">{{ $texto }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-muted transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Accesos rápidos -->
            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Accesos rápidos</p>
                        <h2 class="mt-2 text-title-2 text-ink">¿Qué necesitas hacer?</h2>
                    </div>
                    <a href="{{ route('citas.index') }}" class="hidden min-h-11 items-center gap-1 text-sm font-semibold text-brand-700 transition-colors hover:text-brand-800 sm:inline-flex">
                        Ver agenda completa
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($accesos as $acceso)
                        <a href="{{ $acceso['url'] }}" class="card-boutique group flex h-full flex-col p-6">
                            <span @class([
                                'flex h-12 w-12 items-center justify-center rounded-2xl ring-1 ring-inset transition-colors duration-150',
                                'bg-brand-600 text-white ring-brand-600' => $loop->first,
                                'bg-peach-50 text-brand-700 ring-peach-200 group-hover:bg-peach-100' => ! $loop->first,
                            ])>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $acceso['icono'] }}"/></svg>
                            </span>
                            <h3 class="mt-5 font-serif text-title-3 text-ink">{{ $acceso['titulo'] }}</h3>
                            <p class="mt-1.5 flex-1 text-callout text-muted">{{ $acceso['texto'] }}</p>
                            <span class="mt-5 inline-flex items-center gap-1 text-sm font-semibold text-brand-700">
                                {{ $acceso['accion'] }}
                                <svg class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Consejo de operación -->
            <section class="flex flex-col gap-4 rounded-3xl border border-sage-500/20 bg-sage-50 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-sage-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-ink">Consejo de operación clínica</p>
                        <p class="mt-0.5 text-callout text-ink-soft">Revisa la agenda y los horarios configurados antes de iniciar la jornada para optimizar los tiempos de esterilización y sillón.</p>
                    </div>
                </div>
                <a href="{{ route('admin.horarios.edit') }}" class="btn-secondary shrink-0">Configurar horarios</a>
            </section>
        </div>
    @endif
</x-app-layout>
