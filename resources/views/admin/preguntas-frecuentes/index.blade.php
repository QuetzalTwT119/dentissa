<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Preguntas frecuentes
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Base de conocimiento oficial para el sitio público y el asistente inteligente.</p>
            </div>
            <a href="{{ route('admin.preguntas-frecuentes.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nueva pregunta</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Gestiona las preguntas frecuentes que ven los pacientes en el sitio público.
                    </p>

                    <a href="{{ route('admin.preguntas-frecuentes.create') }}">
                        <x-primary-button>Nueva pregunta</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Orden</th>
                            <th class="px-6 py-3.5">Pregunta</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($preguntas as $pregunta)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $pregunta->orden }}</td>
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $pregunta->pregunta }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $pregunta->activa ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                        {{ $pregunta->activa ? 'Visible' : 'Oculta' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
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
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay preguntas frecuentes registradas todavía.
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
