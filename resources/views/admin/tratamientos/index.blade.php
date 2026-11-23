<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Tratamientos"
            subtitle="Procedimientos odontológicos, tiempos estimados y tarifas de referencia."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-admin.card>

                <x-pagination-summary :items="$tratamientos" label="tratamientos">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.tratamientos.index')" :campos="['buscar', 'activo', 'servicio']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar tratamientos" placeholder="Nombre del tratamiento…" />
                            <x-filtro.select name="activo" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['1' => 'Activos', '0' => 'Inactivos']" />
                            <x-filtro.select name="servicio" etiqueta="Filtrar por especialidad" todos="Todas las especialidades" :opciones="$serviciosFiltro" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.tratamientos.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo tratamiento
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[700px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Duración</th>
                            <th class="px-6 py-3.5">Precio</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($tratamientos as $tratamiento)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $tratamiento->nombre }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $tratamiento->duracion_estimada ? $tratamiento->duracion_estimada.' min' : '—' }}</td>
                            <td class="px-6 py-4 font-medium text-ink whitespace-nowrap">{{ $tratamiento->precioFormateado() ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Servicios visibles que se quedarían sin ningún tratamiento activo.
                                    $quedanVacios = $tratamiento->activo
                                        ? $tratamiento->servicios->filter(fn ($s) => $s->activo && $s->tratamientos_activos_count <= 1)->pluck('nombre')
                                        : collect();
                                    $consecuencia = 'Ya no se podrá agendar en línea ni desde el panel.';
                                    if ($quedanVacios->isNotEmpty()) {
                                        $nombres = '«'.$quedanVacios->implode('», «').'»';
                                        $consecuencia .= ' '.$nombres.($quedanVacios->count() === 1 ? ' se quedará' : ' se quedarán')
                                            .' sin tratamientos y la portada '.($quedanVacios->count() === 1 ? 'la' : 'las').' mostrará como "Agenda por teléfono".';
                                    }
                                @endphp
                                {{-- Interruptor global; sin precio sale bloqueado con el camino para resolverlo. --}}
                                <x-admin.interruptor-estado :action="route('admin.tratamientos.estado', $tratamiento)" :activo="$tratamiento->activo" :nombre="$tratamiento->nombre"
                                                            :desactivar="$consecuencia" activar="Los pacientes podrán agendarlo de nuevo.">
                                    @if (! $tratamiento->activo && ! $tratamiento->precioFormateado())
                                        <x-slot:bloqueo>
                                            {{-- ⚠ como en Ofertas y Usuarios (23/11/2026): falta algo por arreglar. --}}
                                            <span data-falta-precio>
                                                <x-ayuda tono="aviso" etiqueta="Falta precio">
                                                    Sin precio no se puede activar ni agendar.
                                                    <a href="{{ route('admin.tratamientos.edit', $tratamiento) }}" class="mt-1 block font-semibold text-brand-700 underline hover:text-brand-800">Ponerle precio</a>
                                                </x-ayuda>
                                            </span>
                                        </x-slot:bloqueo>
                                    @endif
                                </x-admin.interruptor-estado>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-admin.acciones-fila :editar="route('admin.tratamientos.edit', $tratamiento)" :eliminar="route('admin.tratamientos.destroy', $tratamiento)" :bloqueo-eliminar="$tratamiento->citas_activas ? 'Tiene '.$tratamiento->citas_activas.' '.($tratamiento->citas_activas === 1 ? 'cita activa' : 'citas activas').'. Puedes desactivarlo en lugar de eliminarlo.' : null" que="el tratamiento" confirmar-titulo="¿Eliminar el tratamiento «{{ $tratamiento->nombre }}»?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            title="No hay tratamientos registrados todavía"
                            description="Registra los procedimientos específicos y tarifas para comenzar a agendarlos."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$tratamientos" label="tratamientos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
