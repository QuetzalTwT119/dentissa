<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Servicios"
            subtitle="Catálogo de áreas de atención y especialidades odontológicas."
        >
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-admin.card>

                <x-pagination-summary :items="$servicios" label="servicios">
                    <x-slot:acciones>
                        <a href="{{ route('admin.servicios.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-brand transition-[background-color,transform] duration-150 ease-out hover:bg-brand-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo servicio
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
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
                                $claseFlecha = 'grid h-11 w-11 place-items-center rounded-full text-muted transition-colors duration-150 hover:bg-brand-50 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 disabled:cursor-default disabled:opacity-25 disabled:hover:bg-transparent disabled:hover:text-muted lg:h-8 lg:w-8';
                            @endphp
                            {{-- Las flechas intercambian el servicio con su vecino (ServicioController@mover). --}}
                            <td class="px-6 py-4 whitespace-nowrap" data-posicion-servicio="{{ $posicion }}">
                                <div class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 place-items-center rounded-full bg-brand-50 text-xs font-semibold tabular-nums text-brand-700">{{ $posicion }}</span>
                                    <div class="inline-flex items-center rounded-full border border-line bg-white p-0.5 shadow-2xs">
                                    <form method="POST" action="{{ route('admin.servicios.mover', [$servicio, 'arriba']) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="{{ $claseFlecha }}" @disabled($esPrimero)
                                                title="Subir" aria-label="Subir «{{ $servicio->nombre }}» un lugar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                    </form>
                                    <span class="h-4 w-px bg-line" aria-hidden="true"></span>
                                    <form method="POST" action="{{ route('admin.servicios.mover', [$servicio, 'abajo']) }}">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="{{ $claseFlecha }}" @disabled($esUltimo)
                                                title="Bajar" aria-label="Bajar «{{ $servicio->nombre }}» un lugar">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </form>
                                    </div>
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
                                    <x-admin.acciones-fila :editar="route('admin.servicios.edit', $servicio)" :eliminar="route('admin.servicios.destroy', $servicio)" que="el servicio" confirmar-titulo="¿Eliminar el servicio «{{ $servicio->nombre }}»?" />
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

                <x-pagination-summary :items="$servicios" label="servicios" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
