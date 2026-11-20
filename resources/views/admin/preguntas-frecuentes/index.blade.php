<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Contenido Web"
            title="Preguntas frecuentes"
            subtitle="Base de conocimiento oficial para el sitio público y el asistente inteligente."
        >
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>

                <x-pagination-summary :items="$preguntas" label="preguntas">
                    <x-slot:acciones>
                        <a href="{{ route('admin.preguntas-frecuentes.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-brand transition-[background-color,transform] duration-150 ease-out hover:bg-brand-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nueva pregunta
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
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
                                    <x-admin.acciones-fila :editar="route('admin.preguntas-frecuentes.edit', $pregunta)" :eliminar="route('admin.preguntas-frecuentes.destroy', $pregunta)" que="la pregunta" confirmar-titulo="¿Eliminar la pregunta «{{ $pregunta->pregunta }}»?" />
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

                <x-pagination-summary :items="$preguntas" label="preguntas" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
