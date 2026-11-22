<x-app-layout>
    <x-slot name="header">
        <x-admin.header badge="Gestión Clínica" title="Directorio de Pacientes" description="Directorio oficial de pacientes, tutores e historiales clínicos." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Contenedor Principal de la Tabla -->
            <x-admin.card>

                <!-- Tabla con scroll horizontal responsivo -->
                <x-pagination-summary :items="$pacientes" label="pacientes">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.pacientes.index')" :campos="['buscar']">
                            <x-filtro.buscar etiqueta="Buscar pacientes por nombre, correo o teléfono" placeholder="Nombre, correo o teléfono…" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-4">Paciente</th>
                            <th class="px-6 py-4">Correo</th>
                            <th class="px-6 py-4">Teléfono</th>
                            <th class="px-6 py-4 text-center">Consultas</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($pacientes as $paciente)
                        <tr class="transition-colors hover:bg-brand-50/20 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-admin.user-avatar :name="$paciente->nombre . ' ' . $paciente->apellido" />
                                    <div>
                                        <p class="font-semibold text-ink group-hover:text-brand-700 transition-colors">{{ $paciente->nombre }} {{ $paciente->apellido }}</p>
                                        @if (!empty($paciente->tutor_id) && $paciente->tutor)
                                            <p class="text-caption text-muted flex items-center gap-1 mt-0.5">
                                                <span class="inline-block h-1.5 w-1.5 rounded-full bg-peach-400"></span>
                                                Tutor: {{ $paciente->tutor->nombre }} {{ $paciente->tutor->apellido }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-soft">
                                {{ $paciente->email ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-ink-soft">
                                {{ $paciente->telefono ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{-- El número también abre el historial (22/11/2026). --}}
                                <a href="{{ route('admin.pacientes.historial.index', $paciente) }}"
                                   title="Ver las consultas de {{ $paciente->nombre }}" aria-label="{{ $paciente->historial_clinico_count }} {{ $paciente->historial_clinico_count === 1 ? 'consulta' : 'consultas' }}: ver el historial de {{ $paciente->nombre }}"
                                   class="inline-flex min-h-11 min-w-[2.75rem] items-center justify-center rounded-full border border-line bg-cream px-2.5 text-xs font-semibold text-ink transition hover:border-brand-300 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:min-h-8" data-consultas>
                                    {{ number_format($paciente->historial_clinico_count, 0, '.', ',') }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- Solo íconos, como en todas las listas (22/11/2026): 📋 historial y ✎ editar. --}}
                                <x-admin.acciones-fila :editar="route('admin.pacientes.edit', $paciente)" que="el paciente" :nombre="$paciente->nombre_completo">
                                    <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" title="Historial clínico" aria-label="Historial clínico de {{ $paciente->nombre_completo }}" data-ver-historial
                                       class="grid h-11 w-11 place-items-center rounded-full text-brand-600 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 lg:h-9 lg:w-9">
                                        <x-icon name="expediente" />
                                    </a>
                                </x-admin.acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state colspan="5" title="No se encontraron pacientes">
                            <x-slot:icon>
                                <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </x-slot:icon>
                            <p class="mt-1 text-xs text-muted">
                                @if (!empty($filtros['buscar']))
                                    No hay pacientes que coincidan con "{{ $filtros['buscar'] }}".
                                @else
                                    No hay pacientes registrados todavía.
                                @endif
                            </p>
                        </x-admin.empty-state>
                    @endforelse
                </x-admin.table>
                <x-pagination-summary :items="$pacientes" label="pacientes" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
