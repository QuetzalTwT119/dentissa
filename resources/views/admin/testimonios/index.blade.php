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
        <x-admin.header
            badge="Opiniones y Reseñas"
            title="Testimonios"
            subtitle="Moderación de opiniones y reseñas de pacientes para el sitio público."
        >
            <x-slot:actions>
                <a href="{{ route('admin.testimonios.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo testimonio
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <x-admin.card>
                <div class="flex flex-col items-stretch gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6 border-b border-[#e9dfdb]/80 bg-gradient-to-r from-white via-white to-[#FAF6F6]/50">
                    <form method="GET" action="{{ route('admin.testimonios.index') }}" class="flex flex-wrap items-center gap-3">
                        <label for="filtro_estado" class="text-xs font-semibold uppercase tracking-wider text-[#8A7A7D]">Filtrar por estado:</label>
                        <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                class="rounded-xl border border-[#e9dfdb] bg-white px-3 py-1.5 text-sm text-[#2D2526] shadow-xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Todos</option>
                            <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendiente</option>
                            <option value="aprobado" @selected(($filtros['estado'] ?? '') === 'aprobado')>Aprobado</option>
                            <option value="rechazado" @selected(($filtros['estado'] ?? '') === 'rechazado')>Rechazado</option>
                        </select>
                    </form>

                    <a href="{{ route('admin.testimonios.create') }}" class="self-start sm:self-auto hidden sm:inline-block">
                        <x-secondary-button>Nuevo testimonio</x-secondary-button>
                    </a>
                </div>

                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Paciente</th>
                            <th class="px-6 py-3.5">Calificación</th>
                            <th class="px-6 py-3.5">Comentario</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($testimonios as $testimonio)
                        <tr class="transition hover:bg-[#FAF6F6]/60">
                            <td class="px-6 py-4 font-semibold text-[#2D2526] whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <x-admin.user-avatar :name="$testimonio->paciente->nombre . ' ' . $testimonio->paciente->apellido" />
                                    <span>{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-amber-400 tracking-wider font-bold whitespace-nowrap">{{ str_repeat('★', $testimonio->calificacion) }}</td>
                            <td class="max-w-sm truncate px-6 py-4 text-[#2D2526]" title="{{ $testimonio->comentario }}">{{ $testimonio->comentario }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estadoClase[$testimonio->estado->value] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $estadoLabel[$testimonio->estado->value] ?? $testimonio->estado->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
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
                        <x-admin.empty-state
                            cols="5"
                            title="No hay testimonios registrados con esos criterios"
                            description="Cuando los pacientes dejen sus opiniones o se registren testimonios, aparecerán aquí para su moderación."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$testimonios" label="testimonios" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
