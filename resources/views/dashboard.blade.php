@php
    $esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
@endphp

<x-app-layout>
    @if (! $esAdmin)
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-brand-100 bg-white p-8 text-center shadow-sm">
                <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N. Odontología integral" class="mx-auto h-20 w-auto object-contain">
                <p class="mt-8 text-sm font-medium text-slate-500">Hola, {{ auth()->user()->nombre }}.</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Tu espacio de atención</h1>
                <p class="mx-auto mt-3 max-w-lg text-slate-600">Consulta tus citas y continúa con el asistente de agenda cuando lo necesites.</p>
                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('citas.index') }}" class="rounded-xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600">Ver mis citas</a>
                    <a href="{{ route('asistente-agenda.create') }}" class="rounded-xl border border-brand-200 px-5 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">Abrir asistente</a>
                </div>
            </div>
        </div>
    @else
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-500 px-6 py-8 text-white shadow-lg shadow-brand-200/70 sm:px-10 sm:py-10">
                <div class="absolute -right-12 -top-16 h-56 w-56 rounded-full border-[22px] border-white/10"></div>
                <div class="absolute -bottom-16 right-28 h-40 w-40 rounded-full bg-white/5"></div>
                <div class="relative flex flex-col gap-7 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-brand-100">Panel de administración</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl">Hola, {{ auth()->user()->nombre }}.</h1>
                        <p class="mt-3 max-w-xl text-brand-50/95">Gestiona la agenda, pacientes y contenido de la clínica desde un solo lugar.</p>
                    </div>
                    <a href="{{ route('citas.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-brand-700 shadow-sm transition hover:bg-brand-50">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5" /></svg>
                        Nueva cita
                    </a>
                </div>
            </section>

            <section class="mt-9">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-brand-600">Accesos rápidos</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">¿Qué necesitas hacer?</h2>
                    </div>
                    <a href="{{ route('citas.index') }}" class="hidden text-sm font-semibold text-brand-700 hover:text-brand-800 sm:inline">Ver agenda completa →</a>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ route('citas.create') }}" class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4.5 9.5h15M6 5h12a1.5 1.5 0 0 1 1.5 1.5v12A1.5 1.5 0 0 1 18 20H6a1.5 1.5 0 0 1-1.5-1.5v-12A1.5 1.5 0 0 1 6 5Zm6 7v5m2.5-2.5h-5" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Agendar cita</h3><p class="mt-1 text-sm leading-6 text-slate-600">Registra una nueva cita para un paciente.</p>
                    </a>
                    <a href="{{ route('admin.atencion-oportuna.index') }}" class="rounded-2xl border border-rose-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.5m0 3.5h.01M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Urgencias y espera</h3><p class="mt-1 text-sm leading-6 text-slate-600">Da seguimiento a solicitudes prioritarias.</p>
                    </a>
                    <a href="{{ route('admin.pacientes.index') }}" class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.1A9.5 9.5 0 0 0 21.7 18a4.1 4.1 0 0 0-7.4-2.5M15 19.1v.1A12 12 0 0 1 8.6 21 12 12 0 0 1 2.3 19v-.1A6.4 6.4 0 0 1 14.2 16M12 6.4a3.4 3.4 0 1 1-6.8 0 3.4 3.4 0 0 1 6.8 0Zm8.3 2.2a2.6 2.6 0 1 1-5.3 0 2.6 2.6 0 0 1 5.3 0Z" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Pacientes</h3><p class="mt-1 text-sm leading-6 text-slate-600">Consulta expedientes y datos de contacto.</p>
                    </a>
                    <a href="{{ route('admin.servicios.index') }}" class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><svg viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6" aria-hidden="true"><path d="M8.25 3C6.54 3 5 4.36 5 6.75c0 2.1.52 4.16 1.04 6.75.44 2.16 1.1 6.5 2.74 6.5 1.2 0 1.25-3.05 1.77-4.7.28-.9.55-1.55 1.2-1.55s.92.65 1.2 1.55c.52 1.65.57 4.7 1.77 4.7 1.64 0 2.3-4.34 2.74-6.5.52-2.59 1.04-4.65 1.04-6.75C18.5 4.36 16.96 3 15.25 3c-1.02 0-1.6.5-2.55.5S11.02 3 10 3s-.77.5-1.75.5Z" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Servicios</h3><p class="mt-1 text-sm leading-6 text-slate-600">Actualiza el catálogo de la clínica.</p>
                    </a>
                    <a href="{{ route('admin.casos.index') }}" class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7.5h6m-6 4.5h6m-6 4.5h4.5M6 3.75h12A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Contenido clínico</h3><p class="mt-1 text-sm leading-6 text-slate-600">Administra casos, galería y testimonios.</p>
                    </a>
                    <a href="{{ route('admin.usuarios.index') }}" class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 20.25v-1.5A3.75 3.75 0 0 0 12.75 15h-6A3.75 3.75 0 0 0 3 18.75v1.5m12-12A3.75 3.75 0 1 1 7.5 8.25a3.75 3.75 0 0 1 7.5 0Zm5.25 11.25v-1.5A3.75 3.75 0 0 0 17.5 14.4m-1.25-9.7a3.75 3.75 0 0 1 0 7.1" /></svg></span>
                        <h3 class="mt-5 text-lg font-semibold text-slate-900">Usuarios</h3><p class="mt-1 text-sm leading-6 text-slate-600">Gestiona accesos y permisos del equipo.</p>
                    </a>
                </div>
            </section>

            <section class="mt-8 rounded-2xl border border-brand-100 bg-white px-6 py-5 shadow-sm sm:flex sm:items-center sm:justify-between">
                <div><p class="font-semibold text-slate-900">Consejo de operación</p><p class="mt-1 text-sm text-slate-600">Revisa la agenda y las solicitudes urgentes antes de iniciar la jornada.</p></div>
                <a href="{{ route('admin.horarios.edit') }}" class="mt-4 inline-flex text-sm font-semibold text-brand-700 hover:text-brand-800 sm:mt-0">Configurar horarios →</a>
            </section>
        </div>
    @endif
</x-app-layout>
