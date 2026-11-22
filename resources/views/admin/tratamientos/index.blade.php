<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Catálogo Dental"
            title="Tratamientos"
            subtitle="Procedimientos odontológicos, tiempos estimados y tarifas de referencia."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

            <x-admin.card>

                <x-pagination-summary :items="$tratamientos" label="tratamientos">
                    <x-slot:acciones>
                        <a href="{{ route('admin.tratamientos.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nuevo tratamiento
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[700px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Nombre</th>
                            <th class="px-6 py-3.5">Duración</th>
                            <th class="px-6 py-3.5">Precio</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right lg:text-center">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($tratamientos as $tratamiento)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $tratamiento->nombre }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $tratamiento->duracion_estimada ? $tratamiento->duracion_estimada.' min' : '—' }}</td>
                            <td class="px-6 py-4 font-medium text-ink whitespace-nowrap">{{ $tratamiento->precioFormateado() ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Servicios visibles que se quedarían sin ningún tratamiento activo.
                                    $quedanVacios = $tratamiento->activo
                                        ? $tratamiento->servicios->filter(fn ($s) => $s->activo && $s->tratamientos_activos_count <= 1)->pluck('nombre')
                                        : collect();
                                    $consecuencia = 'Ya no se podrá agendar en línea ni desde el panel.';
                                    if ($quedanVacios->isNotEmpty()) {
                                        $nombres = '«'.$quedanVacios->implode('», «').'»';
                                        $consecuencia .= ' '.$nombres.($quedanVacios->count() === 1 ? ' se quedará' : ' se quedarán')
                                            .' sin tratamientos y la portada '.($quedanVacios->count() === 1 ? 'la' : 'las').' mostrará como "Agenda por teléfono".';
                                    }
                                @endphp
                                @if (! $tratamiento->activo && ! $tratamiento->precioFormateado())
                                    {{-- Sin precio no se publica: interruptor apagado y bloqueado, con el camino para resolverlo. --}}
                                    <div class="flex items-center gap-2.5" data-falta-precio>
                                        <span aria-hidden="true" class="relative h-6 w-11 shrink-0 cursor-not-allowed rounded-full bg-line opacity-60">
                                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm"></span>
                                        </span>
                                        <a href="{{ route('admin.tratamientos.edit', $tratamiento) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-brand-700 hover:text-brand-800 lg:min-h-0">Falta precio</a>
                                    </div>
                                @else
                                    {{-- Clic en el estado: todo cambio pide confirmación (21/11/2026); desactivar explica qué pasará. --}}
                                    <form method="POST" action="{{ route('admin.tratamientos.estado', $tratamiento) }}"
                                          @if ($tratamiento->activo)
                                              data-confirmar="{{ $consecuencia }}"
                                              data-confirmar-titulo="¿Desactivar «{{ $tratamiento->nombre }}»?"
                                              data-confirmar-boton="Sí, desactivar"
                                              data-confirmar-tono="peligro"
                                          @else
                                              data-confirmar="Los pacientes podrán agendarlo de nuevo."
                                              data-confirmar-titulo="¿Activar «{{ $tratamiento->nombre }}»?"
                                              data-confirmar-boton="Sí, activar"
                                              data-confirmar-tono="normal"
                                          @endif>
                                        @csrf
                                        @method('patch')
                                        {{-- Interruptor (no una etiqueta): se reconoce a simple vista como algo que se enciende y apaga. --}}
                                        <button type="submit" data-alternar-estado role="switch" aria-checked="{{ $tratamiento->activo ? 'true' : 'false' }}"
                                                title="{{ $tratamiento->activo ? 'Clic para desactivar' : 'Clic para activar' }}"
                                                aria-label="{{ $tratamiento->nombre }} activo"
                                                class="group inline-flex min-h-11 items-center gap-2.5 rounded-full pr-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
                                            <span aria-hidden="true" class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 {{ $tratamiento->activo ? 'bg-sage-600' : 'bg-line group-hover:bg-peach-300' }}">
                                                <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform duration-200 ease-out motion-reduce:transition-none {{ $tratamiento->activo ? 'translate-x-5' : '' }}"></span>
                                            </span>
                                            <span class="text-sm font-medium {{ $tratamiento->activo ? 'text-sage-700' : 'text-muted' }}">{{ $tratamiento->activo ? 'Activo' : 'Inactivo' }}</span>
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 lg:justify-center">
                                    <x-admin.acciones-fila :editar="route('admin.tratamientos.edit', $tratamiento)" :eliminar="route('admin.tratamientos.destroy', $tratamiento)" que="el tratamiento" confirmar-titulo="¿Eliminar el tratamiento «{{ $tratamiento->nombre }}»?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="5"
                            title="No hay tratamientos registrados todavía"
                            description="Registra los procedimientos específicos y tarifas para comenzar a agendarlos."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$tratamientos" label="tratamientos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
