<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Tratamientos
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Procedimientos odontológicos, tiempos estimados y tarifas de referencia.</p>
            </div>
            <a href="{{ route('admin.tratamientos.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nuevo tratamiento</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Gestiona los procedimientos específicos que ofrece la clínica.
                    </p>

                    <a href="{{ route('admin.tratamientos.create') }}">
                        <x-primary-button>Nuevo tratamiento</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Categoría</th>
                            <th class="px-6 py-3.5">Duración</th>
                            <th class="px-6 py-3.5">Precio</th>
                            <th class="px-6 py-3.5">Servicios</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($tratamientos as $tratamiento)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $tratamiento->nombre }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $tratamiento->categoria ?? '—' }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $tratamiento->duracion_estimada ? $tratamiento->duracion_estimada.' min' : '—' }}</td>
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $tratamiento->precio_referencia ? '$'.$tratamiento->precio_referencia : '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-[#FAF6F6] px-2.5 py-0.5 text-xs font-medium text-[#2D2526] border border-[#e9dfdb]">
                                        {{ $tratamiento->servicios_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $tratamiento->activo ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                        {{ $tratamiento->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.tratamientos.edit', $tratamiento) }}" title="Editar" aria-label="Editar tratamiento"
                                           class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.tratamientos.destroy', $tratamiento) }}"
                                              onsubmit="return confirm('¿Eliminar el tratamiento &quot;{{ $tratamiento->nombre }}&quot;? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar tratamiento"
                                                    class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay tratamientos registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
