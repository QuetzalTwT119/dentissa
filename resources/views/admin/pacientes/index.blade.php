<x-app-layout>
    <x-slot name="header">
        <x-admin.header badge="Gestión Clínica" title="Directorio de Pacientes" description="Directorio oficial de pacientes, tutores e historiales clínicos." />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Contenedor Principal de la Tabla -->
            <x-admin.card>
                <!-- Barra superior con buscador -->
                <x-admin.card-header title="Expedientes registrados" description="Selecciona un paciente para consultar o registrar su historial clínico.">
                    <form method="GET" action="{{ route('admin.pacientes.index') }}" class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <div class="relative w-full sm:w-72">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, correo o teléfono…" aria-label="Buscar pacientes por nombre, correo o teléfono"
                                   class="w-full rounded-xl border border-line bg-white pl-9 pr-3.5 py-2 text-sm text-ink shadow-sm transition placeholder:text-muted/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200 sm:w-72">
                        </div>
                        <x-secondary-button type="submit" class="shrink-0">Buscar</x-secondary-button>
                        @if (!empty($filtros['buscar']))
                            <a href="{{ route('admin.pacientes.index') }}" class="text-sm font-semibold text-brand-600 transition hover:text-brand-800">Limpiar</a>
                        @endif
                    </form>
                </x-admin.card-header>

                <!-- Tabla con scroll horizontal responsivo -->
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
                                <span class="inline-flex items-center justify-center min-w-[2.25rem] rounded-full bg-cream px-2.5 py-1 text-xs font-semibold text-ink border border-line">
                                    {{ $paciente->historial_clinico_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" title="Ver historial clínico" aria-label="Ver historial clínico"
                                       class="inline-flex items-center gap-1 rounded-xl bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 border border-brand-200/60 transition hover:bg-brand-600 hover:text-white hover:border-brand-600 shadow-xs">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                        <span>Historial</span>
                                    </a>
                                    <a href="{{ route('admin.pacientes.edit', $paciente) }}" title="Editar datos" aria-label="Editar datos del paciente"
                                       class="rounded-xl border border-line bg-white p-2 text-muted transition hover:bg-cream hover:text-ink hover:border-brand-200 shadow-xs">
                                        <x-icon name="edit" class="h-4 w-4" />
                                    </a>
                                </div>
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
                <x-pagination-summary :items="$pacientes" label="pacientes" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
