{{--
    Historias clínicas (22/11/2026): lista de pacientes para llenar o actualizar su
    cuestionario. Es la entrada de quien tiene el módulo "historia_clinica" pero no
    el expediente completo (la asistente): aquí no se ven consultas ni notas privadas.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-admin.header badge="Gestión Clínica" title="Historias clínicas" description="Llena o actualiza el cuestionario de salud de cada paciente." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.card>
                <x-pagination-summary :items="$pacientes" label="pacientes">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.historias.index')" :campos="['buscar']">
                            <x-filtro.buscar etiqueta="Buscar pacientes por nombre o teléfono" placeholder="Nombre o teléfono…" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[640px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-4">Paciente</th>
                            <th class="px-6 py-4">Teléfono</th>
                            <th class="px-6 py-4">Historia clínica</th>
                            <th class="px-6 py-4 text-right">Acción</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($pacientes as $paciente)
                        <tr class="group transition-colors hover:bg-brand-50/20" data-fila-historia>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <x-admin.user-avatar :name="$paciente->nombre_completo" />
                                    <p class="font-semibold text-ink">{{ $paciente->nombre_completo }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-ink-soft">{{ $paciente->telefono ?? '—' }}</td>
                            <td class="px-6 py-4">
                                @if ($paciente->historiaClinica)
                                    <x-chip class="border border-sage-200 bg-sage-50 text-sage-800">Llena</x-chip>
                                    <span class="ml-1.5 text-xs text-muted">{{ $paciente->historiaClinica->updated_at?->translatedFormat('j M Y') }}</span>
                                @else
                                    <x-chip class="border border-amber-200 bg-amber-50 text-amber-800">Pendiente</x-chip>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.pacientes.historia.edit', $paciente) }}" data-abrir-historia
                                   class="inline-flex min-h-11 items-center justify-center gap-1.5 whitespace-nowrap rounded-full border border-brand-200 bg-white px-3.5 text-xs font-semibold text-brand-700 shadow-2xs transition hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 lg:min-h-9">
                                    <x-icon name="expediente" />
                                    {{ $paciente->historiaClinica ? 'Ver o editar' : 'Llenar cuestionario' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state colspan="4" title="No se encontraron pacientes">
                            <p class="mt-1 text-xs text-muted">
                                @if (! empty($filtros['buscar']))
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
