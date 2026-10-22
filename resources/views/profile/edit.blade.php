<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                        Ajustes de Cuenta
                    </span>
                </div>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#2D2526]">
                    Perfil de Usuario
                </h2>
                <p class="mt-0.5 text-xs text-[#8A7A7D]">Administra tu información de contacto, credenciales y seguridad.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Resumen de Usuario -->
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#d75078] via-[#e2698d] to-[#f2b0a6] text-xl font-bold text-white shadow-soft">
                        {{ strtoupper(substr(auth()->user()->nombre, 0, 1) . substr(auth()->user()->apellido, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xl font-bold text-[#2D2526]">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</h3>
                            <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200">
                                {{ auth()->user()->rol->name ?? 'Usuario' }}
                            </span>
                        </div>
                        <p class="text-sm text-[#6b5e62] mt-0.5">{{ auth()->user()->email }} &bull; {{ auth()->user()->telefono ?? 'Sin teléfono registrado' }}</p>
                    </div>
                </div>
            </div>

            <!-- Información del Perfil -->
            <div class="p-6 sm:p-8 bg-white border border-[#e9dfdb] shadow-soft rounded-2xl">
                <div class="max-w-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Actualizar Contraseña -->
            <div class="p-6 sm:p-8 bg-white border border-[#e9dfdb] shadow-soft rounded-2xl">
                <div class="max-w-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Zona de Peligro: Eliminar Cuenta -->
            <div class="p-6 sm:p-8 bg-white border border-rose-200/80 shadow-soft rounded-2xl">
                <div class="max-w-2xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
