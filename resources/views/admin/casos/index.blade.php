<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Casos
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Resultados clínicos reales (antes/después) mostrados en la galería de transformaciones.</p>
            </div>
            <a href="{{ route('admin.casos.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nuevo caso</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Casos reales (antes/después) que se muestran en la página principal como resultados del consultorio.
                    </p>

                    <a href="{{ route('admin.casos.create') }}">
                        <x-primary-button>Nuevo caso</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Título</th>
                            <th class="px-6 py-3.5">Descripción</th>
                            <th class="px-6 py-3.5">Fotos</th>
                            <th class="px-6 py-3.5">Orden</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($casos as $caso)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $caso->titulo }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-[#6b5e62]">{{ $caso->descripcion ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-[#FAF6F6] px-2.5 py-0.5 text-xs font-medium text-[#2D2526] border border-[#e9dfdb]">
                                        {{ $caso->imagenes_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $caso->orden }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $caso->activo ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                        {{ $caso->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
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
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay casos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <x-pagination-summary :items="$casos" label="casos" />
            </div>
        </div>
    </div>
</x-app-layout>
