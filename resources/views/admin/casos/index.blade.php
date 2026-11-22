<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Galería Clínica"
            title="Casos"
            subtitle="Resultados clínicos reales (antes/después) mostrados en la galería de transformaciones."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>

                <x-pagination-summary :items="$casos" label="casos">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.casos.index')" :campos="['buscar', 'activo']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar casos" placeholder="Título del caso…" />
                            <x-filtro.select name="activo" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['1' => 'Activos', '0' => 'Inactivos']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.casos.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo caso
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[700px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Título</th>
                            <th class="px-6 py-3.5">Descripción</th>
                            <th class="px-6 py-3.5 text-center">Fotos</th>
                            <th class="px-6 py-3.5 text-center">Orden</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($casos as $caso)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $caso->titulo }}</td>
                            <td class="max-w-sm truncate px-6 py-4 text-ink-soft" title="{{ $caso->descripcion }}">{{ $caso->descripcion ?? '—' }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <x-chip class="bg-cream text-ink border border-line">
                                    {{ $caso->imagenes_count }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 text-center text-ink-soft font-semibold whitespace-nowrap">{{ $caso->orden }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.interruptor-estado :action="route('admin.casos.estado', $caso)" :activo="$caso->activo" :nombre="$caso->titulo"
                                                            desactivar="Dejará de mostrarse en la portada." activar="Se mostrará en la portada." />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-admin.acciones-fila :editar="route('admin.casos.edit', $caso)" :eliminar="route('admin.casos.destroy', $caso)" que="el caso" confirmar-titulo="¿Eliminar el caso «{{ $caso->titulo }}»?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="6"
                            title="No hay casos registrados todavía"
                            description="Registra fotografías y transformaciones clínicas de tus pacientes."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$casos" label="casos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
