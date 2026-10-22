@php
    $esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
@endphp

<x-app-layout>
    @if (! $esAdmin)
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-[#e9dfdb] bg-white p-8 text-center shadow-xl sm:p-12">
                <div class="absolute -right-12 -top-12 h-48 w-48 rounded-full bg-peach-100/40 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-12 -bottom-12 h-48 w-48 rounded-full bg-brand-50/50 blur-2xl pointer-events-none"></div>

                <div class="relative">
                    <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N. Odontología integral" class="mx-auto h-20 w-auto object-contain transition-transform duration-300 hover:scale-105">
                    
                    <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50/80 px-4 py-1 text-xs font-bold text-brand-700">
                        <span class="h-2 w-2 rounded-full bg-brand-500 animate-pulse"></span>
                        Portal de Paciente &bull; Dentissa
                    </div>

                    <p class="mt-4 text-sm font-semibold text-[#8A7A7D]">Hola, {{ auth()->user()->nombre }}.</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-[#2D2526] sm:text-4xl">Tu espacio de atención</h1>
                    <p class="mx-auto mt-3 max-w-lg text-[#6b5e62] leading-relaxed">Consulta tus citas y continúa con el asistente de agenda cuando lo necesites.</p>
                    
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('citas.index') }}" class="inline-flex items-center gap-2 rounded-2xl bg-brand-600 px-7 py-3.5 text-sm font-bold text-white shadow-soft transition duration-200 hover:bg-brand-700 hover:shadow-md hover:-translate-y-0.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Ver mis citas
                        </a>
                        <a href="{{ route('asistente-agenda.create') }}" class="inline-flex items-center gap-2 rounded-2xl border border-[#e9dfdb] bg-white px-7 py-3.5 text-sm font-bold text-[#403539] transition duration-200 hover:bg-[#FAF6F6] hover:border-brand-300 hover:text-brand-700 hover:-translate-y-0.5 shadow-xs">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 text-brand-500" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Abrir asistente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10 space-y-8">
            <!-- Hero Ejecutivo Clínico de Administración -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#231d1e] via-[#3a2028] to-[#732840] p-6 text-white shadow-xl sm:p-10 border border-white/10">
                <div class="absolute -right-16 -top-16 h-72 w-72 rounded-full bg-brand-500/15 blur-2xl pointer-events-none"></div>
                <div class="absolute bottom-0 right-1/4 h-52 w-52 rounded-full bg-peach-400/10 blur-2xl pointer-events-none"></div>
                <div class="absolute left-1/3 top-0 h-40 w-40 rounded-full bg-white/5 blur-xl pointer-events-none"></div>

                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start sm:items-center gap-5">
                        <!-- Avatar de la Doctora / Administrador -->
                        <div class="flex h-16 w-16 sm:h-20 sm:w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#d75078] via-[#e2698d] to-[#f2b0a6] text-white font-bold text-2xl sm:text-3xl shadow-lg ring-4 ring-white/15">
                            {{ strtoupper(substr(auth()->user()->nombre, 0, 1) . substr(auth()->user()->apellido ?? 'L', 0, 1)) }}
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-0.5 text-xs font-bold uppercase tracking-widest text-peach-200 backdrop-blur-xs">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                    Panel de administración
                                </span>
                                <span class="rounded-full bg-emerald-500/20 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-300 border border-emerald-500/30">
                                    En servicio &bull; Dentissa v1.0
                                </span>
                            </div>
                            <h1 class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl text-white">Hola, {{ auth()->user()->nombre }}.</h1>
                            <p class="mt-1 text-xs sm:text-sm text-white/80 max-w-xl leading-relaxed">Gestiona la agenda, pacientes y contenido de la clínica desde un solo lugar.</p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3 pt-2 sm:pt-0">
                        <a href="{{ route('citas.create') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-brand-700 shadow-lg transition-all duration-200 hover:bg-brand-50 hover:shadow-xl hover:-translate-y-0.5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5" /></svg>
                            <span>Nueva cita</span>
                        </a>
                    </div>
                </div>

                <!-- Tira de Indicadores Clínicos Integrados -->
                <div class="mt-8 grid grid-cols-2 gap-3 pt-6 border-t border-white/10 sm:grid-cols-3">
                    <a href="{{ route('citas.index') }}" class="rounded-2xl bg-white/5 p-3.5 backdrop-blur-xs transition hover:bg-white/10 border border-white/5 group">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-peach-200">Agenda Activa</p>
                        <p class="text-sm font-bold text-white mt-0.5 flex items-center justify-between">
                            Consultas de hoy
                            <span class="text-xs text-peach-300 transition group-hover:translate-x-1">→</span>
                        </p>
                    </a>
                    <a href="{{ route('admin.pacientes.index') }}" class="rounded-2xl bg-white/5 p-3.5 backdrop-blur-xs transition hover:bg-white/10 border border-white/5 group">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-teal-300">Expedientes</p>
                        <p class="text-sm font-bold text-white mt-0.5 flex items-center justify-between">
                            Directorio oficial
                            <span class="text-xs text-teal-300 transition group-hover:translate-x-1">→</span>
                        </p>
                    </a>
                    <a href="{{ route('admin.horarios.edit') }}" class="rounded-2xl bg-white/5 p-3.5 backdrop-blur-xs transition hover:bg-white/10 border border-white/5 group">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-amber-300">Horarios</p>
                        <p class="text-sm font-bold text-white mt-0.5 flex items-center justify-between">
                            Turnos configurados
                            <span class="text-xs text-amber-300 transition group-hover:translate-x-1">→</span>
                        </p>
                    </a>
                </div>
            </section>

            <!-- Sección de Módulos Operativos (Accesos Rápidos) -->
            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-brand-500"></span>
                            <p class="text-xs font-bold uppercase tracking-wider text-brand-600">Accesos rápidos</p>
                        </div>
                        <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#2D2526]">¿Qué necesitas hacer?</h2>
                    </div>
                    <a href="{{ route('citas.index') }}" class="hidden text-sm font-bold text-brand-700 hover:text-brand-800 transition sm:inline-flex items-center gap-1 group">
                        <span>Ver agenda completa</span>
                        <span class="transition-transform group-hover:translate-x-1">→</span>
                    </a>
                </div>

                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    
                    <!-- 1. Agendar cita -->
                    <a href="{{ route('citas.create') }}" class="group relative rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-brand-400 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-50 to-peach-100 text-brand-700 border border-brand-200/70 transition-transform duration-300 group-hover:scale-105 group-hover:bg-brand-600 group-hover:text-white shadow-xs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <span class="rounded-full bg-brand-50 px-3 py-1 text-[11px] font-bold text-brand-700 border border-brand-200/60">Agenda rápida</span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-[#2D2526] group-hover:text-brand-700 transition-colors">Agendar cita</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6b5e62]">Registra una nueva cita para un paciente.</p>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-brand-700 pt-3 border-t border-[#e9dfdb]/60">
                            <span>Abrir agenda</span>
                            <span class="transition-transform group-hover:translate-x-1">→</span>
                        </div>
                    </a>

                    <!-- 2. Pacientes -->
                    <a href="{{ route('admin.pacientes.index') }}" class="group relative rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-teal-400 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-50 to-emerald-100 text-teal-700 border border-teal-200/70 transition-transform duration-300 group-hover:scale-105 group-hover:bg-teal-600 group-hover:text-white shadow-xs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </span>
                            <span class="rounded-full bg-teal-50 px-3 py-1 text-[11px] font-bold text-teal-800 border border-teal-200/60">Expedientes</span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-[#2D2526] group-hover:text-teal-700 transition-colors">Pacientes</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6b5e62]">Consulta expedientes y datos de contacto.</p>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-teal-700 pt-3 border-t border-[#e9dfdb]/60">
                            <span>Ver directorio médico</span>
                            <span class="transition-transform group-hover:translate-x-1">→</span>
                        </div>
                    </a>

                    <!-- 4. Servicios -->
                    <a href="{{ route('admin.servicios.index') }}" class="group relative rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-purple-400 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-50 to-fuchsia-100 text-purple-700 border border-purple-200/70 transition-transform duration-300 group-hover:scale-105 group-hover:bg-purple-600 group-hover:text-white shadow-xs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </span>
                            <span class="rounded-full bg-purple-50 px-3 py-1 text-[11px] font-bold text-purple-800 border border-purple-200/60">Catálogo</span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-[#2D2526] group-hover:text-purple-700 transition-colors">Servicios</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6b5e62]">Actualiza el catálogo de la clínica.</p>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-purple-700 pt-3 border-t border-[#e9dfdb]/60">
                            <span>Gestionar catálogo</span>
                            <span class="transition-transform group-hover:translate-x-1">→</span>
                        </div>
                    </a>

                    <!-- 5. Contenido clínico -->
                    <a href="{{ route('admin.casos.index') }}" class="group relative rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-amber-400 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-50 to-orange-100 text-amber-700 border border-amber-200/70 transition-transform duration-300 group-hover:scale-105 group-hover:bg-amber-600 group-hover:text-white shadow-xs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-bold text-amber-800 border border-amber-200/60">Difusión</span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-[#2D2526] group-hover:text-amber-700 transition-colors">Contenido clínico</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6b5e62]">Administra casos, galería y testimonios.</p>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-amber-700 pt-3 border-t border-[#e9dfdb]/60">
                            <span>Ver publicaciones</span>
                            <span class="transition-transform group-hover:translate-x-1">→</span>
                        </div>
                    </a>

                    <!-- 6. Usuarios -->
                    <a href="{{ route('admin.usuarios.index') }}" class="group relative rounded-3xl border border-[#e9dfdb] bg-white p-6 shadow-soft transition-all duration-300 hover:-translate-y-1.5 hover:border-blue-400 hover:shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-100 text-blue-700 border border-blue-200/70 transition-transform duration-300 group-hover:scale-105 group-hover:bg-blue-600 group-hover:text-white shadow-xs">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-bold text-blue-800 border border-blue-200/60">Seguridad</span>
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-[#2D2526] group-hover:text-blue-700 transition-colors">Usuarios</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-[#6b5e62]">Gestiona accesos y permisos del equipo.</p>
                        <div class="mt-4 flex items-center gap-1 text-xs font-bold text-blue-700 pt-3 border-t border-[#e9dfdb]/60">
                            <span>Gestionar equipo</span>
                            <span class="transition-transform group-hover:translate-x-1">→</span>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Consejo y Operación de Guardia -->
            <section class="rounded-3xl border border-[#e9dfdb] bg-gradient-to-r from-white via-white to-brand-50/50 p-6 shadow-md sm:flex sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 text-brand-700 shadow-xs">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-base text-[#2D2526]">Consejo de operación clínica</p>
                        <p class="mt-0.5 text-sm text-[#6b5e62]">Revisa la agenda y los horarios configurados antes de iniciar la jornada para optimizar los tiempos de esterilización y sillón.</p>
                    </div>
                </div>
                <a href="{{ route('admin.horarios.edit') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-brand-700 hover:text-brand-800 transition sm:mt-0 group shrink-0">
                    <span>Configurar horarios</span>
                    <span class="transition-transform group-hover:translate-x-1">→</span>
                </a>
            </section>
        </div>
    @endif
</x-app-layout>
