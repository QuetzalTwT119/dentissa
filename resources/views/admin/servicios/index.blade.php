<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Especialidades"
            subtitle="Catálogo de áreas de atención y especialidades odontológicas."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-admin.card>

                <x-pagination-summary :items="$servicios" label="servicios">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.servicios.index')" :campos="['buscar', 'activo']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar servicios" placeholder="Nombre del servicio…" />
                            <x-filtro.select name="activo" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['1' => 'Disponibles', '0' => 'No disponibles']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.servicios.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo servicio
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[700px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Orden en la portada</th>
                            <th class="px-6 py-3.5 text-center">Tratamientos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($servicios as $servicio)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $servicio->nombre }}</td>
                            @php
                                // Posición real en el carrusel, aunque la lista esté filtrada.
                                $posicion = $posiciones[$servicio->id];
                                $esPrimero = $posicion === 1;
                                $esUltimo = $posicion === $totalServicios;
                            @endphp
                            {{--
                                Orden en la portada (22/11/2026, rediseño pedido por el usuario): el número
                                a secas y dos flechas redondas como los demás botones del panel (antes una
                                cápsula con borde y una rayita). La flecha que no aplica (subir al primero,
                                bajar al último) queda oculta en vez de apagada, sin mover las demás.
                                Las flechas intercambian el servicio con su vecino (ServicioController@mover).
                            --}}
                            <td class="px-6 py-4 whitespace-nowrap" data-posicion-servicio="{{ $posicion }}">
                                <div class="flex items-center gap-1">
                                    <span class="w-6 text-sm font-semibold tabular-nums text-ink">{{ $posicion }}</span>
                                    {{-- Con filtro, el vecino de la lista no es el del carrusel: las flechas
                                         moverían junto a uno oculto. Se ordena con la lista completa. --}}
                                    @if ($filtrando)
                                    <x-ayuda etiqueta="Orden con filtro">Para cambiar el orden, quita el filtro: las flechas mueven el servicio junto a su vecino en la lista completa.</x-ayuda>
                                    @else
                                    <span @class(['invisible' => $esPrimero])>
                                    <x-admin.boton-fila :action="route('admin.servicios.mover', [$servicio, 'arriba'])" color="muted" :deshabilitado="$esPrimero"
                                                        :etiqueta="'Subir «'.$servicio->nombre.'» un lugar'"
                                                        :titulo="'¿Subir «'.$servicio->nombre.'» un lugar?'"
                                                        mensaje="Cambia el orden en que se muestran los servicios."
                                                        boton="Sí, subir">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                    </x-admin.boton-fila>
                                    </span>
                                    <span @class(['invisible' => $esUltimo])>
                                    <x-admin.boton-fila :action="route('admin.servicios.mover', [$servicio, 'abajo'])" color="muted" :deshabilitado="$esUltimo"
                                                        :etiqueta="'Bajar «'.$servicio->nombre.'» un lugar'"
                                                        :titulo="'¿Bajar «'.$servicio->nombre.'» un lugar?'"
                                                        mensaje="Cambia el orden en que se muestran los servicios."
                                                        boton="Sí, bajar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </x-admin.boton-fila>
                                    </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <x-chip class="bg-cream text-ink border border-line">
                                    {{ $servicio->tratamientos_count }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.interruptor-estado :action="route('admin.servicios.estado', $servicio)" :activo="$servicio->activo" :nombre="$servicio->nombre"
                                                            textoActivo="Disponible" textoInactivo="No disponible"
                                                            desactivar="Se ocultará de la portada y ya no se podrá agendar (en línea, en el panel, en el asistente ni por Telegram). Las citas ya agendadas no cambian."
                                                            activar="Se mostrará en la portada y se podrá agendar de nuevo." />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-admin.acciones-fila :editar="route('admin.servicios.edit', $servicio)" :eliminar="route('admin.servicios.destroy', $servicio)" :bloqueo-eliminar="$servicio->citas_activas ? 'Tiene '.$servicio->citas_activas.' '.($servicio->citas_activas === 1 ? 'cita activa' : 'citas activas').'. Puedes desactivarlo en lugar de eliminarlo.' : null" que="el servicio" confirmar-titulo="¿Eliminar el servicio «{{ $servicio->nombre }}»?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            :title="$filtrando ? 'No hay servicios con esos criterios' : 'No hay servicios registrados todavía'"
                            :description="$filtrando ? 'Cambia la búsqueda o el estado para ver otros servicios.' : 'Comienza agregando las áreas de atención y especialidades que ofrece Dentissa.'"
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$servicios" label="servicios" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
