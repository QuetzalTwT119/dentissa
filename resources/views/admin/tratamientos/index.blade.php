<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Tratamientos"
            subtitle="Procedimientos odontológicos, tiempos estimados y tarifas de referencia."
        >
            <x-slot:actions>
                <a href="{{ route('admin.tratamientos.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo tratamiento
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif

            <x-admin.card>
                <x-admin.card-header
                    title="Procedimientos clínicos"
                    description="Gestiona los procedimientos específicos que ofrece la clínica."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.tratamientos.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nuevo tratamiento</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[700px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Categoría</th>
                            <th class="px-6 py-3.5">Duración</th>
                            <th class="px-6 py-3.5">Precio</th>
                            <th class="px-6 py-3.5 text-center">Servicios</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($tratamientos as $tratamiento)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $tratamiento->nombre }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $tratamiento->categoria ?? '—' }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $tratamiento->duracion_estimada ? $tratamiento->duracion_estimada.' min' : '—' }}</td>
                            <td class="px-6 py-4 font-medium text-ink whitespace-nowrap">{{ $tratamiento->precio_referencia ? '$'.$tratamiento->precio_referencia : '—' }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-ink border border-line">
                                    {{ $tratamiento->servicios_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$tratamiento->activo" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
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
                        <x-admin.empty-state
                            cols="7"
                            title="No hay tratamientos registrados todavía"
                            description="Registra los procedimientos específicos y tarifas para comenzar a agendarlos."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$tratamientos" label="tratamientos" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
