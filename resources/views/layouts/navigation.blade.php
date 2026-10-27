@php
$usuario = auth()->user();
$esAdmin = $usuario->rol === \App\Enums\RolUsuario::Admin;
$notificacionesNuevas = $usuario->notificacionesSistema()->whereNull('vista_en')->count();
$notificacionesRecientes = $usuario->notificacionesSistema()->latest('id')->limit(5)->get();
$clasesEnlace = 'inline-flex min-h-11 items-center rounded-full px-4 text-sm font-semibold text-[#5c4f53] transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300';
$clasesEnlaceActivo = 'inline-flex min-h-11 items-center rounded-full bg-brand-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2';
@endphp

<nav x-data="{ open: false, notificationsOpen: false }" class="relative z-40 border-b border-[#e9dfdb] bg-white/95 backdrop-blur-md">
    <div class="mx-auto flex min-h-[5.5rem] max-w-screen-2xl items-center justify-between gap-4 px-4 sm:px-6 2xl:px-10">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 shrink-0 items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-300" aria-label="Dentissa, ir al inicio">
            <img src="/images/brand/logo-rosa-transparente.png" alt="" class="h-12 w-12 rounded-full border border-brand-100 bg-white object-cover p-0.5" width="48" height="48">
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate text-lg font-bold tracking-tight text-brand-700">Dentissa</span>
                <span class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $esAdmin ? 'Administración' : 'Asistencia' }}</span>
            </span>
        </a>

        <div class="hidden min-w-0 flex-1 items-center justify-center gap-1 2xl:flex">
            <a href="{{ route('dashboard') }}" @class([$clasesEnlaceActivo => request()->routeIs('dashboard'), $clasesEnlace => ! request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('citas.index') }}" @class([$clasesEnlaceActivo => request()->routeIs('citas.*'), $clasesEnlace => ! request()->routeIs('citas.*')]) @if(request()->routeIs('citas.*')) aria-current="page" @endif>Agenda</a>
            <a href="{{ route('asistente-agenda.create') }}" @class([$clasesEnlaceActivo => request()->routeIs('asistente-agenda.*'), $clasesEnlace => ! request()->routeIs('asistente-agenda.*')]) @if(request()->routeIs('asistente-agenda.*')) aria-current="page" @endif>Asistente</a>

            @unless ($esAdmin)
                <span class="mx-2 h-8 w-px bg-brand-100" aria-hidden="true"></span>
                <a href="{{ route('ofertas.index') }}" @class([$clasesEnlaceActivo => request()->routeIs('ofertas.*'), $clasesEnlace => ! request()->routeIs('ofertas.*')]) @if(request()->routeIs('ofertas.*')) aria-current="page" @endif>Ofertas</a>
                <a href="{{ route('galeria.index') }}" @class([$clasesEnlaceActivo => request()->routeIs('galeria.*'), $clasesEnlace => ! request()->routeIs('galeria.*')]) @if(request()->routeIs('galeria.*')) aria-current="page" @endif>Galería</a>
            @endunless

            @if ($esAdmin)
                <span class="mx-2 h-8 w-px bg-brand-100" aria-hidden="true"></span>
                <x-nav-dropdown :active="request()->routeIs('admin.pacientes.*') || request()->routeIs('admin.usuarios.*') || request()->routeIs('admin.reportes.*') || request()->routeIs('admin.mensajes.*') || request()->routeIs('admin.auditoria.*')">
                    <x-slot name="label">Gestión</x-slot>
                    <x-dropdown-link :href="route('admin.pacientes.index')">Pacientes</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.usuarios.index')">Usuarios</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.reportes.index')">Reportes</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.mensajes.index')">Mensajes</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.auditoria.index')">Auditoría</x-dropdown-link>
                </x-nav-dropdown>

                <x-nav-dropdown :active="request()->routeIs('admin.servicios.*') || request()->routeIs('admin.tratamientos.*')">
                    <x-slot name="label">Catálogo</x-slot>
                    <x-dropdown-link :href="route('admin.servicios.index')">Servicios</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.tratamientos.index')">Tratamientos</x-dropdown-link>
                </x-nav-dropdown>

                <x-nav-dropdown :active="request()->routeIs('admin.ofertas.*') || request()->routeIs('admin.testimonios.*') || request()->routeIs('admin.galeria.*') || request()->routeIs('admin.casos.*') || request()->routeIs('admin.preguntas-frecuentes.*')">
                    <x-slot name="label">Contenido</x-slot>
                    <x-dropdown-link :href="route('admin.ofertas.index')">Ofertas</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.testimonios.index')">Testimonios</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.galeria.index')">Galería</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.casos.index')">Casos</x-dropdown-link>
                    <x-dropdown-link :href="route('admin.preguntas-frecuentes.index')">Preguntas frecuentes</x-dropdown-link>
                </x-nav-dropdown>

                <x-nav-dropdown :active="request()->routeIs('admin.horarios.*')">
                    <x-slot name="label">Configuración</x-slot>
                    <x-dropdown-link :href="route('admin.horarios.edit')">Horarios</x-dropdown-link>
                </x-nav-dropdown>
            @endif
        </div>

        <div class="hidden shrink-0 items-center gap-2 2xl:flex">
            <div class="relative">
                <button type="button" data-notification-preview-trigger @click.stop="notificationsOpen = !notificationsOpen" :aria-expanded="notificationsOpen.toString()" aria-controls="notification-preview-panel" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-500 transition hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-300" aria-label="Ver notificaciones recientes" title="Ver notificaciones recientes">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022 23.848 23.848 0 0 0 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span data-notification-count-badge class="absolute right-1 top-1 inline-flex min-w-4 -translate-y-1/4 translate-x-1/4 items-center justify-center rounded-full bg-brand-600 px-1 text-[0.65rem] font-bold leading-4 text-white {{ $notificacionesNuevas > 0 ? '' : 'hidden' }}" @if ($notificacionesNuevas === 0) hidden @endif>{{ $notificacionesNuevas > 99 ? '99+' : $notificacionesNuevas }}</span>
                </button>

            </div>

            <div class="relative" x-data="{ profileOpen: false }" @click.outside="profileOpen = false" @keydown.escape.window="profileOpen = false">
                <button type="button" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen.toString()" class="inline-flex min-h-11 max-w-56 items-center gap-2 rounded-full border border-[#e9dfdb] bg-[#faf7f4] px-4 text-sm font-semibold text-[#382d31] transition hover:border-brand-300 hover:bg-brand-50/70 focus:outline-none focus:ring-2 focus:ring-brand-300">
                    @if ($usuario->foto_perfil)
                        <img src="{{ route('profile.photo.show') }}" alt="" class="h-7 w-7 shrink-0 rounded-full object-cover" width="28" height="28">
                    @else
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700" aria-hidden="true">{{ strtoupper(substr($usuario->nombre, 0, 1).substr($usuario->apellido, 0, 1)) }}</span>
                    @endif
                    <span class="truncate">{{ $usuario->nombre }} {{ $usuario->apellido }}</span>
                    <svg class="h-4 w-4 shrink-0 text-[#8A7A7D]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                <div x-cloak x-show="profileOpen" x-transition.origin.top.right class="absolute right-0 top-14 z-50 w-52 overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white py-1.5 shadow-xl ring-1 ring-black/5" @click="profileOpen = false">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm font-medium text-[#403539] transition hover:bg-brand-50 hover:text-brand-700">Mi perfil</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="block w-full px-4 py-2.5 text-left text-sm font-medium text-[#403539] transition hover:bg-brand-50 hover:text-brand-700">Cerrar sesión</button></form>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1 2xl:hidden">
            <button type="button" data-notification-preview-trigger @click.stop="notificationsOpen = !notificationsOpen" :aria-expanded="notificationsOpen.toString()" aria-label="Ver notificaciones recientes" class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-300">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022 23.848 23.848 0 0 0 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                <span data-notification-count-badge class="absolute right-1 top-1 inline-flex min-w-4 -translate-y-1/4 translate-x-1/4 items-center justify-center rounded-full bg-brand-600 px-1 text-[0.65rem] font-bold leading-4 text-white {{ $notificacionesNuevas > 0 ? '' : 'hidden' }}" @if ($notificacionesNuevas === 0) hidden @endif>{{ $notificacionesNuevas > 99 ? '99+' : $notificacionesNuevas }}</span>
            </button>
            <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-label="open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación'" aria-controls="mobile-navigation-menu" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-300">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true"><path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>

    <div id="notification-preview-panel" data-notification-preview-panel x-cloak x-show="notificationsOpen" @click.outside="notificationsOpen = false" @keydown.escape.window="notificationsOpen = false" x-transition.origin.top.right class="fixed left-4 right-4 top-20 z-50 2xl:absolute 2xl:left-auto 2xl:right-40 2xl:top-20 2xl:w-[26rem]" role="dialog" aria-label="Notificaciones recientes">
        <section class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-2xl ring-1 ring-black/5">
            <header class="flex items-center justify-between border-b border-[#e9dfdb]/70 bg-[#FAF6F6] px-4 py-3.5">
                <div><h2 class="font-semibold text-[#2c2428]">Notificaciones recientes</h2><p class="mt-0.5 text-xs text-[#8A7A7D]"><span data-notification-count-summary>{{ $notificacionesNuevas }}</span> nuevas</p></div>
                <button type="button" @click="notificationsOpen = false" class="rounded-lg p-2 text-[#8A7A7D] transition hover:bg-white hover:text-[#2c2428] focus:outline-none focus:ring-2 focus:ring-brand-primary" aria-label="Cerrar notificaciones"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 0 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 0 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg></button>
            </header>
            <div data-notification-preview-list class="max-h-[min(60vh,26rem)] divide-y divide-[#e9dfdb]/60 overflow-y-auto">
                @forelse ($notificacionesRecientes as $notificacion)
                    <a data-notification-preview="{{ $notificacion->id }}" data-notification-read-url="{{ route('notificaciones.leida', $notificacion, false) }}" href="{{ $notificacion->url ?: route('citas.index', [], false) }}" @click="notificationsOpen = false" class="flex gap-3 px-4 py-3.5 transition hover:bg-[#faf7f4] focus:bg-[#faf7f4] focus:outline-none"><span data-notification-preview-dot class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $notificacion->leida_en ? 'bg-slate-300' : 'bg-brand-600 ring-4 ring-brand-100 animate-pulse' }}" aria-label="{{ $notificacion->leida_en ? 'Notificación leída' : 'Notificación nueva' }}"></span><span class="min-w-0 flex-1"><span class="flex items-start justify-between gap-3"><span data-notification-preview-title class="notification-preview-title text-sm font-semibold text-[#2c2428]">{{ $notificacion->titulo }}</span><time class="shrink-0 text-xs text-[#8A7A7D]" datetime="{{ $notificacion->created_at->toIso8601String() }}">{{ $notificacion->created_at->diffForHumans() }}</time></span><span data-notification-preview-message class="notification-preview-message mt-1 text-sm leading-5 text-[#665b5e]">{{ $notificacion->mensaje }}</span></span></a>
                @empty
                    <p data-notification-preview-empty class="px-4 py-8 text-center text-sm text-[#8A7A7D]">No hay notificaciones recientes.</p>
                @endforelse
            </div>
            <footer class="border-t border-[#e9dfdb]/70 bg-[#FAF6F6] px-4 py-3 text-center"><a href="{{ route('notificaciones.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800 transition" @click="notificationsOpen = false">Ver todas las notificaciones</a></footer>
        </section>
    </div>

    <div id="mobile-navigation-menu" x-cloak x-show="open" x-transition class="border-t border-brand-100 bg-white px-4 pb-4 pt-2 2xl:hidden">
        <div class="grid gap-1 sm:grid-cols-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Inicio</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('citas.index')" :active="request()->routeIs('citas.*')">Agenda</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('asistente-agenda.create')" :active="request()->routeIs('asistente-agenda.*')">Asistente</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notificaciones.index')" :active="request()->routeIs('notificaciones.*')">Notificaciones <span data-notification-count-badge class="ms-2 rounded-full bg-brand-600 px-2 py-0.5 text-xs text-white {{ $notificacionesNuevas > 0 ? '' : 'hidden' }}" @if ($notificacionesNuevas === 0) hidden @endif>{{ $notificacionesNuevas > 99 ? '99+' : $notificacionesNuevas }}</span></x-responsive-nav-link>
            @unless ($esAdmin)
                <x-responsive-nav-link :href="route('ofertas.index')" :active="request()->routeIs('ofertas.*')">Ofertas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('galeria.index')" :active="request()->routeIs('galeria.*')">Galería</x-responsive-nav-link>
            @endunless
            @if ($esAdmin)
                <p class="col-span-full px-3 pt-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Gestión</p>
                <x-responsive-nav-link :href="route('admin.pacientes.index')" :active="request()->routeIs('admin.pacientes.*')">Pacientes</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.usuarios.index')" :active="request()->routeIs('admin.usuarios.*')">Usuarios</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.reportes.index')" :active="request()->routeIs('admin.reportes.*')">Reportes</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.mensajes.index')" :active="request()->routeIs('admin.mensajes.*')">Mensajes</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.auditoria.index')" :active="request()->routeIs('admin.auditoria.*')">Auditoría</x-responsive-nav-link>
                <p class="col-span-full px-3 pt-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Catálogo y contenido</p>
                <x-responsive-nav-link :href="route('admin.servicios.index')" :active="request()->routeIs('admin.servicios.*')">Servicios</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.tratamientos.index')" :active="request()->routeIs('admin.tratamientos.*')">Tratamientos</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.ofertas.index')" :active="request()->routeIs('admin.ofertas.*')">Ofertas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.testimonios.index')" :active="request()->routeIs('admin.testimonios.*')">Testimonios</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.galeria.index')" :active="request()->routeIs('admin.galeria.*')">Galería</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.casos.index')" :active="request()->routeIs('admin.casos.*')">Casos</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.preguntas-frecuentes.index')" :active="request()->routeIs('admin.preguntas-frecuentes.*')">Preguntas frecuentes</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.horarios.edit')" :active="request()->routeIs('admin.horarios.*')">Horarios</x-responsive-nav-link>
            @endif
        </div>
        <div class="mt-3 border-t border-gray-100 pt-3">
            <div class="flex items-center gap-3 px-3">
                @if ($usuario->foto_perfil)
                    <img src="{{ route('profile.photo.show') }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover" width="40" height="40">
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700" aria-hidden="true">{{ strtoupper(substr($usuario->nombre, 0, 1).substr($usuario->apellido, 0, 1)) }}</span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ $usuario->nombre }} {{ $usuario->apellido }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $usuario->email }}</p>
                </div>
            </div>
            <div class="mt-2 grid gap-1 sm:grid-cols-2">
                <x-responsive-nav-link :href="route('profile.edit')">Mi perfil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">@csrf<x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Cerrar sesión</x-responsive-nav-link></form>
            </div>
        </div>
    </div>
</nav>
