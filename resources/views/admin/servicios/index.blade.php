<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Servicios"
            subtitle="Catálogo de áreas de atención y especialidades odontológicas."
        >
            <x-slot:actions>
                <a href="{{ route('admin.servicios.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo servicio
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('error'))
                <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif

            <x-admin.card>
                <x-admin.card-header
                    title="Servicios clínicos"
                    description="Gestiona los servicios ofrecidos por la clínica y sus tratamientos asociados."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.servicios.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nuevo servicio</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[700px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Orden</th>
                            <th class="px-6 py-3.5 text-center">Tratamientos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($servicios as $servicio)
                        <tr class="transition hover:bg-[#FAF6F6]/60">
                            <td class="px-6 py-4 font-semibold text-[#2D2526] whitespace-nowrap">{{ $servicio->nombre }}</td>
                            <td class="px-6 py-4 text-[#6b5e62] whitespace-nowrap">{{ $servicio->orden }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-[#FAF6F6] px-2.5 py-0.5 text-xs font-semibold text-[#2D2526] border border-[#e9dfdb]">
                                    {{ $servicio->tratamientos_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$servicio->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.servicios.edit', $servicio) }}" title="Editar" aria-label="Editar servicio"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.servicios.destroy', $servicio) }}"
                                          onsubmit="return confirm('¿Eliminar el servicio &quot;{{ $servicio->nombre }}&quot;? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar servicio"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            title="No hay servicios registrados todavía"
                            description="Comienza agregando las áreas de atención y especialidades que ofrece Dentissa."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$servicios" label="servicios" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
