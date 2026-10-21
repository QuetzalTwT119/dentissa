@php
$estadoLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
$estadoClase = [
    'pendiente' => 'bg-amber-50 text-amber-800 border border-amber-200/60',
    'aprobado' => 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20',
    'rechazado' => 'bg-rose-50 text-rose-800 border border-rose-200/60',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Testimonios
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Moderación de opiniones y reseñas de pacientes para el sitio público.</p>
            </div>
            <a href="{{ route('admin.testimonios.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nuevo testimonio</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-col items-stretch gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6 border-b border-[#e9dfdb]/80">
                    <form method="GET" action="{{ route('admin.testimonios.index') }}" class="flex flex-wrap items-center gap-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#8A7A7D]">Filtrar por estado:</span>
                        <select name="estado" onchange="this.form.submit()"
                                class="rounded-xl border border-[#e9dfdb] bg-white px-3 py-2 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Todos</option>
                            <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendiente</option>
                            <option value="aprobado" @selected(($filtros['estado'] ?? '') === 'aprobado')>Aprobado</option>
                            <option value="rechazado" @selected(($filtros['estado'] ?? '') === 'rechazado')>Rechazado</option>
                        </select>
                    </form>

                    <a href="{{ route('admin.testimonios.create') }}" class="self-start sm:self-auto">
                        <x-primary-button>Nuevo testimonio</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Paciente</th>
                            <th class="px-6 py-3.5">Calificación</th>
                            <th class="px-6 py-3.5">Comentario</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($testimonios as $testimonio)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}</td>
                                <td class="px-6 py-4 text-amber-400 tracking-wider font-bold">{{ str_repeat('★', $testimonio->calificacion) }}</td>
                                <td class="max-w-sm truncate px-6 py-4 text-[#2D2526]">{{ $testimonio->comentario }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $estadoClase[$testimonio->estado->value] }}">
                                        {{ $estadoLabel[$testimonio->estado->value] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('admin.testimonios.edit', $testimonio) }}" title="Editar" aria-label="Editar testimonio"
                                           class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                            <x-icon name="edit" />
                                        </a>
                                        @if ($testimonio->estado->value !== 'aprobado')
                                            <form method="POST" action="{{ route('admin.testimonios.aprobar', $testimonio) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Aprobar" aria-label="Aprobar testimonio"
                                                        class="rounded-xl p-2 text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800">
                                                    <x-icon name="check" />
                                                </button>
                                            </form>
                                        @endif
                                        @if ($testimonio->estado->value !== 'rechazado')
                                            <form method="POST" action="{{ route('admin.testimonios.rechazar', $testimonio) }}">
                                                @csrf
                                                @method('patch')
                                                <button type="submit" title="Rechazar" aria-label="Rechazar testimonio"
                                                        class="rounded-xl p-2 text-amber-600 transition hover:bg-amber-50 hover:text-amber-800">
                                                    <x-icon name="x" />
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.testimonios.destroy', $testimonio) }}"
                                              onsubmit="return confirm('¿Eliminar este testimonio? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar testimonio"
                                                    class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay testimonios registrados con esos criterios.
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
