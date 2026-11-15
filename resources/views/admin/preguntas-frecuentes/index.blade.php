<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Contenido Web"
            title="Preguntas frecuentes"
            subtitle="Base de conocimiento oficial para el sitio público y el asistente inteligente."
        >
            <x-slot:actions>
                <a href="{{ route('admin.preguntas-frecuentes.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva pregunta
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>
                <x-admin.card-header
                    title="Preguntas y respuestas"
                    description="Gestiona las preguntas frecuentes que ven los pacientes en el sitio público."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.preguntas-frecuentes.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nueva pregunta</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

                <x-admin.table minWidth="min-w-[700px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5 text-center">Orden</th>
                            <th class="px-6 py-3.5">Pregunta</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($preguntas as $pregunta)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 text-center text-ink-soft font-semibold whitespace-nowrap">{{ $pregunta->orden }}</td>
                            <td class="px-6 py-4 font-semibold text-ink">{{ $pregunta->pregunta }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge
                                    :active="$pregunta->activa"
                                    activeText="Visible"
                                    inactiveText="Oculta"
                                />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.preguntas-frecuentes.edit', $pregunta) }}" title="Editar" aria-label="Editar pregunta"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.preguntas-frecuentes.destroy', $pregunta) }}"
                                          onsubmit="return confirm('¿Eliminar la pregunta &quot;{{ $pregunta->pregunta }}&quot;? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar pregunta"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="4"
                            title="No hay preguntas frecuentes registradas todavía"
                            description="Agrega preguntas y respuestas para orientar a los pacientes en el sitio web."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$preguntas" label="preguntas" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
