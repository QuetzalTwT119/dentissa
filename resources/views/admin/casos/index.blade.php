<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Galería Clínica"
            title="Casos"
            subtitle="Resultados clínicos reales (antes/después) mostrados en la galería de transformaciones."
        >
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>

                <x-pagination-summary :items="$casos" label="casos">
                    <x-slot:acciones>
                        <a href="{{ route('admin.casos.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-brand transition-[background-color,transform] duration-150 ease-out hover:bg-brand-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
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
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-ink border border-line">
                                    {{ $caso->imagenes_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-ink-soft font-semibold whitespace-nowrap">{{ $caso->orden }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$caso->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.casos.edit', $caso) }}" title="Editar" aria-label="Editar caso"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.casos.destroy', $caso) }}"
                                          data-confirmar="Esta acción no se puede deshacer." data-confirmar-titulo="¿Eliminar el caso «{{ $caso->titulo }}»?" data-confirmar-boton="Sí, eliminar" data-confirmar-tono="peligro">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar caso"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
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
