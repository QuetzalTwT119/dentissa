<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Ajustes de cuenta"
            title="Perfil de usuario"
            subtitle="Administra tu información de contacto, credenciales y seguridad."
        />
    </x-slot>

    <div class="panel-pagina py-7 sm:py-10">
        <div class="panel-contenido mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8"
                 x-data="{ seccion: @js($errors->updatePassword->isNotEmpty() || session('status') === 'password-updated' ? 'password' : 'info') }"
                 x-init="if (location.hash === '#profile-password') seccion = 'password'">
            <section aria-label="Resumen de la cuenta" class="card-boutique overflow-hidden">
                <div class="flex flex-col gap-5 bg-gradient-to-r from-white via-white to-brand-50/50 p-5 sm:flex-row sm:items-center sm:p-7 lg:px-6 lg:py-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 via-[#e2698d] to-peach-300 text-xl font-semibold text-white shadow-soft ring-4 ring-brand-50 sm:h-[4.5rem] sm:w-[4.5rem]">
                        @if (auth()->user()->foto_perfil)
                            <img src="{{ route('profile.photo.show') }}" alt="Foto de perfil de {{ auth()->user()->nombre }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->nombre, 0, 1) . substr(auth()->user()->apellido, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="break-words font-serif text-title-2 text-ink">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</h3>
                            <span class="badge-brand">{{ auth()->user()->rol->name ?? 'Usuario' }}</span>
                        </div>
                        <div class="mt-2 flex flex-col gap-x-5 gap-y-1 text-sm text-ink-soft sm:flex-row sm:flex-wrap">
                            <span class="inline-flex min-w-0 items-center gap-2 break-all">
                                <svg class="h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75 12 12.5l8.25-5.75M5.25 5.25h13.5a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5Z" /></svg>
                                {{ auth()->user()->email }}
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 3.75h2.1c.6 0 1.12.41 1.26.99l.77 3.08a1.3 1.3 0 0 1-.47 1.36l-1.4 1.08a14.2 14.2 0 0 0 6.23 6.23l1.08-1.4a1.3 1.3 0 0 1 1.36-.47l3.08.77c.58.14.99.66.99 1.26v2.1c0 .72-.58 1.3-1.3 1.3C10.67 20.05 3.95 13.33 3.95 5.05c0-.72.58-1.3 1.3-1.3Z" /></svg>
                                {{ auth()->user()->telefono ?? 'Sin teléfono registrado' }}
                            </span>
                        </div>
                    </div>
                    <a href="#profile-information" @click.prevent="seccion = 'info'; $nextTick(() => document.getElementById('nombre')?.focus())" class="btn-secondary min-h-11 shrink-0 self-start sm:self-center">Editar perfil</a>
                </div>
            </section>

            {{-- Pestañas (20/11/2026): antes las secciones iban apiladas y la página se desplazaba;
                 ahora se ve una a la vez. Con errores de contraseña abre esa pestaña. --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">
                <nav aria-label="Secciones del perfil" class="card-boutique min-w-0 p-4 lg:sticky lg:top-28">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-label text-muted">Tu cuenta</p>
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                        <a href="#profile-information" @click.prevent="seccion = 'info'" :aria-current="seccion === 'info' ? 'page' : null" :class="seccion === 'info' ? 'bg-brand-50 font-semibold text-brand-700' : 'font-medium text-ink-soft hover:bg-cream'" class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:w-full">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white text-brand-600 shadow-2xs" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
                            </span>
                            Información personal
                        </a>
                        <a href="#profile-password" @click.prevent="seccion = 'password'" :aria-current="seccion === 'password' ? 'page' : null" :class="seccion === 'password' ? 'bg-brand-50 font-semibold text-brand-700' : 'font-medium text-ink-soft hover:bg-cream'" class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:w-full">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-cream text-muted" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v2"/></svg>
                            </span>
                            Contraseña
                        </a>
                        <a href="{{ route('dos-pasos.show') }}" class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:bg-cream hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:w-full">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-cream text-muted" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path stroke-linecap="round" d="M11 18.5h2"/></svg>
                            </span>
                            Verificación en dos pasos
                            @if (auth()->user()->tieneDosPasos())
                                <span class="ml-auto rounded-full bg-sage-50 px-2 py-0.5 text-caption font-semibold text-sage-700">Activa</span>
                            @endif
                        </a>
                    </div>
                </nav>

                <div class="min-w-0 space-y-6 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:items-start lg:gap-6 lg:space-y-0">
                    <section class="card-boutique p-5 sm:p-7 lg:p-5" aria-label="Foto de perfil" x-show="seccion === 'info'">
                        @include('profile.partials.update-profile-photo-form')
                    </section>
                    <section id="profile-information" class="card-boutique scroll-mt-28 p-5 sm:p-7 lg:p-5" aria-label="Información personal" x-show="seccion === 'info'">
                        @include('profile.partials.update-profile-information-form')
                    </section>

                    <section id="profile-password" class="card-boutique scroll-mt-28 p-5 sm:p-7 lg:col-span-2" aria-label="Cambiar contraseña" x-show="seccion === 'password'" x-cloak>
                        @include('profile.partials.update-password-form')
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
