<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">Ajustes de cuenta</p>
            <h2 class="mt-1.5 text-[1.7rem] leading-tight text-ink sm:text-3xl">Perfil de usuario</h2>
            <p class="mt-1 text-sm text-muted">Administra tu información de contacto, credenciales y seguridad.</p>
        </div>
    </x-slot>

    <div class="py-7 sm:py-10">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <section aria-label="Resumen de la cuenta" class="card-boutique overflow-hidden">
                <div class="flex flex-col gap-5 bg-gradient-to-r from-white via-white to-brand-50/50 p-5 sm:flex-row sm:items-center sm:p-7">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-brand-500 via-[#e2698d] to-peach-300 text-xl font-bold text-white shadow-soft ring-4 ring-brand-50 sm:h-[4.5rem] sm:w-[4.5rem]">
                        @if (auth()->user()->foto_perfil)
                            <img src="{{ route('profile.photo.show') }}" alt="Foto de perfil de {{ auth()->user()->nombre }}" class="h-full w-full object-cover">
                        @else
                            {{ strtoupper(substr(auth()->user()->nombre, 0, 1) . substr(auth()->user()->apellido, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="break-words font-serif text-2xl text-ink sm:text-[1.7rem]">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</h3>
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
                    <a href="#profile-information" class="btn-secondary min-h-11 shrink-0 self-start sm:self-center">Editar perfil</a>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-start">
                <nav aria-label="Secciones del perfil" class="card-boutique p-4 lg:sticky lg:top-28">
                    <p class="px-3 pb-2 text-xs font-bold uppercase tracking-[0.12em] text-muted">Tu cuenta</p>
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                        <a href="#profile-information" class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl bg-brand-50 px-3 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-300 lg:w-full">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-brand-600 shadow-sm" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" /></svg>
                            </span>
                            Información personal
                        </a>
                        <a href="#profile-password" class="inline-flex min-h-11 shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:bg-cream hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300 lg:w-full">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cream text-muted" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v2"/></svg>
                            </span>
                            Contraseña
                        </a>
                    </div>
                </nav>

                <div class="min-w-0 space-y-6">
                    <section class="card-boutique p-5 sm:p-7" aria-label="Foto de perfil">
                        @include('profile.partials.update-profile-photo-form')
                    </section>
                    <section id="profile-information" class="card-boutique scroll-mt-28 p-5 sm:p-7" aria-label="Información personal">
                        @include('profile.partials.update-profile-information-form')
                    </section>

                    <section id="profile-password" class="card-boutique scroll-mt-28 p-5 sm:p-7" aria-label="Cambiar contraseña">
                        @include('profile.partials.update-password-form')
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
