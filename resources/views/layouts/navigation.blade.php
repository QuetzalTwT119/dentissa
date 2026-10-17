@php
$esAdmin = auth()->user()->rol === \App\Enums\RolUsuario::Admin;
@endphp

<nav x-data="{ open: false }" class="relative z-50 border-b border-brand-100 bg-white/95 shadow-[0_8px_24px_rgba(197,53,105,0.06)] backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 justify-between gap-4">
            <div class="flex">
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl px-1 py-2 transition hover:bg-brand-50" aria-label="Ir al panel principal">
                        <img src="/images/brand/logo-rosa-transparente.png" alt="Melissa López N. Odontología integral" class="h-11 w-11 rounded-full object-contain">
                        <span class="hidden leading-tight lg:block">
                            <span class="block text-sm font-bold text-brand-700">Dentissa</span>
                            <span class="block text-[11px] font-medium uppercase tracking-[0.14em] text-slate-400">Administración</span>
                        </span>
                    </a>
                </div>

                <div class="hidden items-center gap-1 sm:ms-6 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Inicio
                    </x-nav-link>

                    <x-nav-link :href="route('citas.index')" :active="request()->routeIs('citas.*')">
                        Agenda
                    </x-nav-link>

                    <x-nav-link :href="route('asistente-agenda.create')" :active="request()->routeIs('asistente-agenda.*')">
                        Asistente
                    </x-nav-link>

                    @unless ($esAdmin)
                        <x-nav-link :href="route('ofertas.index')" :active="request()->routeIs('ofertas.index')">
                            Ofertas
                        </x-nav-link>
                        <x-nav-link :href="route('galeria.index')" :active="request()->routeIs('galeria.index')">
                            Galería
                        </x-nav-link>
                    @endunless

                    @if ($esAdmin)
                        <span class="mx-1 h-7 w-px bg-brand-100"></span>

                        <x-nav-dropdown :active="request()->routeIs('admin.pacientes.*') || request()->routeIs('admin.usuarios.*') || request()->routeIs('admin.reportes.*')">
                            <x-slot name="label">Gestión</x-slot>

                            <x-dropdown-link :href="route('admin.pacientes.index')">Pacientes</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.usuarios.index')">Usuarios</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.reportes.index')">Reportes</x-dropdown-link>
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

                        <x-nav-dropdown :active="request()->routeIs('admin.horarios.*') || request()->routeIs('admin.mensajes.*')">
                            <x-slot name="label">Configuración</x-slot>

                            <x-dropdown-link :href="route('admin.horarios.edit')">Horarios</x-dropdown-link>
                            <x-dropdown-link :href="route('admin.mensajes.index')">Mensajes</x-dropdown-link>
                        </x-nav-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden shrink-0 sm:ms-4 sm:flex sm:items-center">
                <div class="relative ms-3">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center rounded-full border border-brand-100 bg-brand-50 px-3 py-2 text-sm font-medium leading-4 text-slate-600 transition duration-150 ease-in-out hover:border-brand-200 hover:text-brand-700 focus:outline-none">
                                {{ auth()->user()->nombre }} {{ auth()->user()->apellido }}

                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                Mi perfil
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    Cerrar sesión
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('citas.index')" :active="request()->routeIs('citas.*')">
                Citas
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('asistente-agenda.create')" :active="request()->routeIs('asistente-agenda.*')">
                Asistente
            </x-responsive-nav-link>

            @unless ($esAdmin)
                <x-responsive-nav-link :href="route('ofertas.index')" :active="request()->routeIs('ofertas.index')">
                    Ofertas
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('galeria.index')" :active="request()->routeIs('galeria.index')">
                    Galería
                </x-responsive-nav-link>
            @endunless

            @if ($esAdmin)
                <x-responsive-nav-link :href="route('admin.servicios.index')" :active="request()->routeIs('admin.servicios.*')">
                    Servicios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.tratamientos.index')" :active="request()->routeIs('admin.tratamientos.*')">
                    Tratamientos
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.pacientes.index')" :active="request()->routeIs('admin.pacientes.*')">
                    Pacientes
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.usuarios.index')" :active="request()->routeIs('admin.usuarios.*')">
                    Usuarios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.reportes.index')" :active="request()->routeIs('admin.reportes.*')">
                    Reportes
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.auditoria.index')" :active="request()->routeIs('admin.auditoria.*')">
                    Auditoría
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.testimonios.index')" :active="request()->routeIs('admin.testimonios.*')">
                    Testimonios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.ofertas.index')" :active="request()->routeIs('admin.ofertas.*')">
                    Ofertas
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.galeria.index')" :active="request()->routeIs('admin.galeria.*')">
                    Galería
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.horarios.edit')" :active="request()->routeIs('admin.horarios.*')">
                    Horarios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.mensajes.index')" :active="request()->routeIs('admin.mensajes.*')">
                    Mensajes
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.casos.index')" :active="request()->routeIs('admin.casos.*')">
                    Casos
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.preguntas-frecuentes.index')" :active="request()->routeIs('admin.preguntas-frecuentes.*')">
                    Preguntas frecuentes
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
                <div class="text-base font-medium text-gray-800">{{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</div>
                <div class="text-sm font-medium text-gray-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Mi perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
