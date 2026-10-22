<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Galería Clínica"
            title="Casos"
            subtitle="Resultados clínicos reales (antes/después) mostrados en la galería de transformaciones."
        >
            <x-slot:actions>
                <a href="{{ route('admin.casos.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo caso
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>
                <x-admin.card-header
                    title="Casos de éxito y sonrisas"
                    description="Casos reales (antes/después) que se muestran en la página principal como resultados del consultorio."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.casos.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nuevo caso</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

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
                        <tr class="transition hover:bg-[#FAF6F6]/60">
                            <td class="px-6 py-4 font-semibold text-[#2D2526] whitespace-nowrap">{{ $caso->titulo }}</td>
                            <td class="max-w-sm truncate px-6 py-4 text-[#6b5e62]" title="{{ $caso->descripcion }}">{{ $caso->descripcion ?? '—' }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-[#FAF6F6] px-2.5 py-0.5 text-xs font-semibold text-[#2D2526] border border-[#e9dfdb]">
                                    {{ $caso->imagenes_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-[#6b5e62] font-semibold whitespace-nowrap">{{ $caso->orden }}</td>
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
                                          onsubmit="return confirm('¿Eliminar el caso &quot;{{ $caso->titulo }}&quot;? Esta acción no se puede deshacer.');">
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

                <x-pagination-summary :items="$casos" label="casos" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
