@php
$estadoLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
$estadoClase = [
    'pendiente' => 'bg-amber-50 text-amber-800 border border-amber-200/60',
    'aprobado' => 'bg-sage-50 text-sage-500 border border-sage-500/20',
    'rechazado' => 'bg-rose-50 text-rose-800 border border-rose-200/60',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Opiniones y Reseñas"
            title="Testimonios"
            subtitle="Moderación de opiniones y reseñas de pacientes para el sitio público."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>

                <x-pagination-summary :items="$testimonios" label="testimonios">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.testimonios.index')" :campos="['buscar', 'estado']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar testimonios" placeholder="Paciente o comentario…" />
                            <x-filtro.select name="estado" etiqueta="Filtrar por estado" todos="Todos los testimonios" :opciones="['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados', 'rechazado' => 'Rechazados']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Paciente</th>
                            <th class="px-6 py-3.5">Calificación</th>
                            <th class="px-6 py-3.5">Comentario</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right lg:text-center">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($testimonios as $testimonio)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-admin.user-avatar :name="$testimonio->paciente->nombre . ' ' . $testimonio->paciente->apellido" />
                                    {{-- Nombre largo: se recorta (completo al pasar el mouse) para no sacar Acciones de la tarjeta. --}}
                                    <span class="block max-w-[14rem] truncate" title="{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}">{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-amber-400 tracking-label font-semibold whitespace-nowrap">{{ str_repeat('★', $testimonio->calificacion) }}</td>
                            {{-- Para moderar hay que leerlo entero: 3 líneas y "Leer completo" (antes una línea cortada). --}}
                            <td class="min-w-[16rem] max-w-[26rem] whitespace-normal px-6 py-4 text-ink">
                                <x-texto-expandible :texto="$testimonio->comentario" lineas="3" />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-chip class="{{ $estadoClase[$testimonio->estado->value] ?? 'bg-shell text-ink' }}">
                                    {{ $estadoLabel[$testimonio->estado->value] ?? $testimonio->estado->value }}
                                </x-chip>
                            </td>
                            <td class="w-px whitespace-nowrap px-6 py-4 text-right">
                                <x-admin.acciones-fila :eliminar="route('admin.testimonios.destroy', $testimonio)" que="el testimonio" confirmar-titulo="¿Eliminar este testimonio?">
                                    {{-- Moderación: ícono + palabra (22/11/2026; solo el ícono no decía qué hacía). --}}
                                    @if ($testimonio->estado->value !== 'aprobado')
                                        <x-admin.boton-fila :action="route('admin.testimonios.aprobar', $testimonio)" icono="check" color="sage" texto="Aprobar"
                                                            :etiqueta="'Aprobar testimonio de '.$testimonio->paciente?->nombre"
                                                            titulo="¿Aprobar y publicar este testimonio?"
                                                            mensaje="Se mostrará en la portada con el nombre y la inicial del paciente."
                                                            boton="Sí, aprobar" />
                                    @endif
                                    @if ($testimonio->estado->value !== 'rechazado')
                                        <x-admin.boton-fila :action="route('admin.testimonios.rechazar', $testimonio)" icono="x" color="amber" texto="Rechazar"
                                                            :etiqueta="'Rechazar testimonio de '.$testimonio->paciente?->nombre"
                                                            titulo="¿Rechazar este testimonio?"
                                                            :mensaje="$testimonio->estado->value === 'aprobado' ? 'Dejará de mostrarse en la portada. Podrás aprobarlo después.' : 'No se mostrará en la portada. Podrás aprobarlo después.'"
                                                            boton="Sí, rechazar" />
                                    @endif
                                </x-admin.acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            title="No hay testimonios registrados con esos criterios"
                            description="Cuando los pacientes dejen sus opiniones o se registren testimonios, aparecerán aquí para su moderación."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$testimonios" label="testimonios" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
