@php
$estadoLabel = ['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'];
$estadoClase = [
    'pendiente' => 'bg-amber-50 text-amber-800 border border-amber-200/60',
    'aprobado' => 'bg-sage-50 text-sage-500 border border-sage-500/20',
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
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <x-alert type="error" :message="$errors->first()" />
            @endif

            <x-admin.card>

                <x-pagination-summary :items="$testimonios" label="testimonios">
                    <x-slot:filtros>
                        <form method="GET" action="{{ route('admin.testimonios.index') }}" class="flex items-center gap-2">
                            <label for="filtro_estado" class="text-xs font-medium text-ink-soft">Estado</label>
                            <select id="filtro_estado" name="estado" onchange="this.form.submit()"
                                    class="min-h-11 rounded-xl border border-field bg-white py-1 pl-3 pr-8 text-xs font-semibold text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                <option value="">Todos</option>
                                <option value="pendiente" @selected(($filtros['estado'] ?? '') === 'pendiente')>Pendientes</option>
                                <option value="aprobado" @selected(($filtros['estado'] ?? '') === 'aprobado')>Aprobados</option>
                                <option value="rechazado" @selected(($filtros['estado'] ?? '') === 'rechazado')>Rechazados</option>
                            </select>
                        </form>
                    </x-slot:filtros>
                </x-pagination-summary>
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
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-admin.user-avatar :name="$testimonio->paciente->nombre . ' ' . $testimonio->paciente->apellido" />
                                    {{-- Nombre largo: se recorta (completo al pasar el mouse) para no sacar Acciones de la tarjeta. --}}
                                    <span class="block max-w-[14rem] truncate" title="{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}">{{ $testimonio->paciente->nombre }} {{ $testimonio->paciente->apellido }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-amber-400 tracking-label font-semibold whitespace-nowrap">{{ str_repeat('★', $testimonio->calificacion) }}</td>
                            <td class="max-w-[18rem] truncate px-6 py-4 text-ink" title="{{ $testimonio->comentario }}">{{ $testimonio->comentario }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $estadoClase[$testimonio->estado->value] ?? 'bg-shell text-ink' }}">
                                    {{ $estadoLabel[$testimonio->estado->value] ?? $testimonio->estado->value }}
                                </span>
                            </td>
                            <td class="w-px whitespace-nowrap px-6 py-4 text-right">
                                <x-admin.acciones-fila :eliminar="route('admin.testimonios.destroy', $testimonio)" que="el testimonio" confirmar-titulo="¿Eliminar este testimonio?">
                                    {{-- Moderación: mismos botones redondos que Editar/Eliminar. --}}
                                    @if ($testimonio->estado->value !== 'aprobado')
                                        <form method="POST" action="{{ route('admin.testimonios.aprobar', $testimonio) }}" class="inline-flex">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="Aprobar y publicar" aria-label="Aprobar testimonio de {{ $testimonio->paciente?->nombre }}"
                                                    class="grid h-11 w-11 place-items-center rounded-full text-sage-700 transition-colors duration-150 hover:bg-sage-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-500/40 lg:h-9 lg:w-9">
                                                <x-icon name="check" />
                                            </button>
                                        </form>
                                    @endif
                                    @if ($testimonio->estado->value !== 'rechazado')
                                        <form method="POST" action="{{ route('admin.testimonios.rechazar', $testimonio) }}" class="inline-flex">
                                            @csrf
                                            @method('patch')
                                            <button type="submit" title="Rechazar" aria-label="Rechazar testimonio de {{ $testimonio->paciente?->nombre }}"
                                                    class="grid h-11 w-11 place-items-center rounded-full text-amber-700 transition-colors duration-150 hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300 lg:h-9 lg:w-9">
                                                <x-icon name="x" />
                                            </button>
                                        </form>
                                    @endif
                                    <span class="mx-0.5 h-5 w-px bg-line" aria-hidden="true"></span>
                                </x-admin.acciones-fila>
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

                <x-pagination-summary :items="$testimonios" label="testimonios" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
