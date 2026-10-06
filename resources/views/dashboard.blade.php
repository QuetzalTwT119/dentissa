@php
$esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
@endphp

@if (! $esAdmin)
    <x-app-layout>
        <x-slot name="header">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        Bienvenid@, {{ auth()->user()->nombre }}.
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
@else
    <x-app-layout>
        <div class="mx-auto max-w-3xl px-6 py-16 text-center">
            <img src="/images/brand/icono-rosa.jpg" alt="Melissa López N." class="mx-auto h-20 w-20 rounded-full shadow-sm shadow-brand-200">
            <p class="mt-4 font-cursive text-3xl text-brand-600">Melissa López N.</p>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-400">Odontología integral</p>

            <h1 class="mt-10 text-3xl font-semibold text-gray-900">
                Bienvenid@, {{ auth()->user()->nombre }}
            </h1>

            <div class="mt-10 grid gap-6 sm:grid-cols-2">
                <a href="{{ route('citas.create') }}" class="flex flex-col items-center justify-center gap-4 rounded-xl border border-brand-100 bg-white px-6 py-10 text-brand-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15.75h6M12 12.75v6" />
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">Agendar Cita</span>
                </a>
                <a href="{{ route('admin.atencion-oportuna.index') }}" class="flex flex-col items-center justify-center gap-4 rounded-xl border border-red-100 bg-white px-6 py-10 text-red-600 shadow-sm transition hover:-translate-y-0.5 hover:border-red-200 hover:shadow-md">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">Urgencias y espera</span>
                </a>
                <a href="{{ route('admin.servicios.index') }}" class="flex flex-col items-center justify-center gap-4 rounded-xl border border-brand-100 bg-white px-6 py-10 text-brand-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-10 w-10">
                        <path d="M8.25 3c-1.71 0-3.25 1.36-3.25 3.75 0 2.1.52 4.16 1.04 6.75.44 2.16 1.1 6.5 2.74 6.5 1.2 0 1.25-3.05 1.77-4.7.28-.9.55-1.55 1.2-1.55s.92.65 1.2 1.55c.52 1.65.57 4.7 1.77 4.7 1.64 0 2.3-4.34 2.74-6.5.52-2.59 1.04-4.65 1.04-6.75C18.5 4.36 16.96 3 15.25 3c-1.02 0-1.6.5-2.55.5S11.02 3 10 3s-.77.5-1.75.5Z" />
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">Servicios</span>
                </a>
                <a href="{{ route('admin.usuarios.index') }}" class="flex flex-col items-center justify-center gap-4 rounded-xl border border-brand-100 bg-white px-6 py-10 text-brand-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.13a9.4 9.4 0 0 0 6.75-.58 4.13 4.13 0 0 0-7.53-2.49M15 19.13v-.01c0-1.11-.28-2.16-.79-3.07M15 19.13v.1A12.32 12.32 0 0 1 8.62 21c-2.33 0-4.51-.64-6.37-1.77l-.01-.1a6.38 6.38 0 0 1 11.96-3.08M12 6.38a3.38 3.38 0 1 1-6.75 0 3.38 3.38 0 0 1 6.75 0Zm8.25 2.24a2.63 2.63 0 1 1-5.25 0 2.63 2.63 0 0 1 5.25 0Z" />
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">Usuarios</span>
                </a>
                <a href="{{ route('admin.casos.index') }}" class="flex flex-col items-center justify-center gap-4 rounded-xl border border-brand-100 bg-white px-6 py-10 text-brand-600 shadow-sm transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-md">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke="currentColor" class="h-10 w-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4.5h6m-6-9h6M5.25 20.25h13.5A2.25 2.25 0 0 0 21 18V6.75a2.25 2.25 0 0 0-2.25-2.25H16.5v-.75a2.25 2.25 0 0 0-2.25-2.25h-4.5A2.25 2.25 0 0 0 7.5 3.75v.75H5.25A2.25 2.25 0 0 0 3 6.75V18a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <span class="text-lg font-semibold text-gray-900">Casos</span>
                </a>
            </div>
        </div>
    </x-app-layout>
@endif
