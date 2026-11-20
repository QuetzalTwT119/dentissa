<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Servicios"
            subtitle="Catálogo de áreas de atención y especialidades odontológicas."
        >
            <x-slot:actions>
                <a href="{{ route('admin.servicios.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo servicio
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif

            <x-admin.card>
                <x-admin.card-header
                    title="Servicios clínicos"
                    description="Gestiona los servicios ofrecidos por la clínica y sus tratamientos asociados."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.servicios.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nuevo servicio</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[700px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Posición en el carrusel</th>
                            <th class="px-6 py-3.5 text-center">Tratamientos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($servicios as $servicio)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $servicio->nombre }}</td>
                            @php
                                $posicion = $servicios->firstItem() + $loop->index;
                                $esPrimero = $posicion === 1;
                                $esUltimo = $posicion === $servicios->total();
                                $claseFlecha = 'inline-flex h-11 w-11 items-center justify-center rounded-xl border border-line bg-white text-ink-soft transition hover:border-peach-300 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 disabled:cursor-not-allowed disabled:opacity-35 disabled:hover:border-line disabled:hover:text-ink-soft lg:h-9 lg:w-9';
                            @endphp
                            {{-- Las flechas intercambian el servicio con su vecino (ServicioController@mover). --}}
                            <td class="px-6 py-4 whitespace-nowrap" data-posicion-servicio="{{ $posicion }}">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-6 text-sm font-semibold text-ink-soft tabular-nums">{{ $posicion }}</span>
                                    <form method="POST" action="{{ route('admin.servicios.mover', [$servicio, 'arriba']) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="{{ $claseFlecha }}" @disabled($esPrimero)
                                                title="Subir" aria-label="Subir «{{ $servicio->nombre }}» un lugar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.servicios.mover', [$servicio, 'abajo']) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="{{ $claseFlecha }}" @disabled($esUltimo)
                                                title="Bajar" aria-label="Bajar «{{ $servicio->nombre }}» un lugar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-ink border border-line">
                                    {{ $servicio->tratamientos_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$servicio->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.servicios.edit', $servicio) }}" title="Editar" aria-label="Editar servicio"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.servicios.destroy', $servicio) }}"
                                          onsubmit="return confirm('¿Eliminar el servicio &quot;{{ $servicio->nombre }}&quot;? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar servicio"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            title="No hay servicios registrados todavía"
                            description="Comienza agregando las áreas de atención y especialidades que ofrece Dentissa."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$servicios" label="servicios" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
