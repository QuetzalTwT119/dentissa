<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Promociones"
            title="Ofertas"
            subtitle="Una oferta nueva queda En revisión; publícala con ✓ para que se vea en la portada."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>

                <x-pagination-summary :items="$ofertas" label="ofertas">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.ofertas.index')" :campos="['buscar', 'estado', 'tratamiento']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar ofertas" placeholder="Título de la oferta…" />
                            <x-filtro.select name="estado" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['pendiente' => 'En revisión', 'activa' => 'Publicadas', 'inactiva' => 'Ocultas']" />
                            <x-filtro.select name="tratamiento" etiqueta="Filtrar por tratamiento" todos="Todos los tratamientos" :opciones="$tratamientosFiltro" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.ofertas.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nueva oferta
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]" :larga="true">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-3.5">Título</th>
                            <th class="px-6 py-3.5">Beneficio</th>
                            <th class="px-6 py-3.5">Tratamiento</th>
                            <th class="px-6 py-3.5">Vigencia</th>
                            <th class="px-6 py-3.5 text-center">Cupos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($ofertas as $oferta)
                        <tr class="transition hover:bg-cream/60">
                            {{-- El título puede ocupar 2 líneas: en una sola, uno largo empujaba Acciones fuera de la tabla. --}}
                            <td class="min-w-[10rem] max-w-[15rem] px-6 py-4 font-semibold leading-snug text-ink">{{ $oferta->titulo }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">
                                <x-chip class="bg-peach-50 text-brand-700 border border-peach-200">
                                    {{ $oferta->etiquetaBeneficio() }}
                                </x-chip>
                            </td>
                            <td class="min-w-[8rem] px-6 py-4 leading-snug text-ink-soft">
                                <span class="inline-flex items-center gap-0.5">
                                    {{ $oferta->tratamiento->nombre ?? '—' }}
                                    {{-- Tratamiento que ya no se agenda en línea: símbolo con la explicación (antes una frase larga). --}}
                                    @if ($oferta->tratamiento && (! $oferta->tratamiento->activo || ! $oferta->tratamiento->servicios_activos))
                                        <x-ayuda tono="aviso" etiqueta="Tratamiento inactivo" data-tratamiento-inactivo>
                                            Esta oferta no se muestra en la portada ni se puede publicar. Activa el tratamiento en Catálogo → Tratamientos o elige otro al editarla.
                                        </x-ayuda>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $oferta->fecha_inicio->locale('es')->translatedFormat('j M') }} – {{ $oferta->fecha_fin->locale('es')->translatedFormat('j M Y') }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <x-chip class="bg-cream text-ink border border-line">
                                    {{-- Lugares ocupados por citas reales agendadas con la oferta. --}}
                                    {{ $oferta->cupo_total > 0 ? $oferta->lugaresUsados().'/'.$oferta->cupo_total : 'Sin límite' }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    // Una "Publicada" que no se ve (vencida, tratamiento inactivo, sin lugares
                                    // o fuera del carrusel) lo dice con su motivo (22/11/2026).
                                    $noVisible = $oferta->motivoNoVisible($enPortada);
                                    $colorEstado = $noVisible
                                        ? 'bg-rose-50 text-rose-800 ring-rose-300/60'
                                        : ['pendiente' => 'bg-amber-50 text-amber-800 ring-amber-500/25', 'activa' => 'bg-sage-50 text-sage-700 ring-sage-500/20', 'inactiva' => 'bg-shell text-ink-soft ring-line'][$oferta->estado->value];
                                @endphp
                                <span class="inline-flex items-center gap-0.5">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $colorEstado }}" data-estado-oferta="{{ $oferta->estado->value }}" @if ($noVisible) data-no-visible @endif>{{ $noVisible ? 'Publicada, no se ve' : $oferta->estado->etiqueta() }}</span>
                                    @if ($noVisible)
                                        <x-ayuda tono="aviso" etiqueta="Por qué no se ve">{{ $noVisible }}</x-ayuda>
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- Mismo grupo que Editar/Eliminar, sin separador (21/11/2026). --}}
                                <x-admin.acciones-fila :editar="route('admin.ofertas.edit', $oferta)" :eliminar="route('admin.ofertas.destroy', $oferta)" que="la oferta" confirmar-titulo="¿Eliminar la oferta «{{ $oferta->titulo }}»?">
                                    {{--
                                        En revisión u oculta: ✓ publica, solo si se puede (22/11/2026: antes
                                        salía ✓ en ofertas vencidas o de tratamiento inactivo y el servidor
                                        las rechazaba). Si no se puede, ⚠ en su lugar dice por qué.
                                        Publicada: ocultar (sigue aquí para reactivarla).
                                    --}}
                                    @php $noPublicable = $oferta->estado !== \App\Enums\EstadoOferta::Activa ? $oferta->motivoNoPublicable() : null; @endphp
                                    @if ($noPublicable)
                                        {{-- Publicar (ojo) apagado con su ⚠, igual que la papelera bloqueada (22/11/2026). --}}
                                        <span class="grid h-11 w-11 place-items-center lg:h-9 lg:w-9" data-no-publicable>
                                            <x-ayuda tono="aviso" etiqueta="No se puede publicar">
                                                <x-slot:icono>
                                                    <span class="relative grid place-items-center">
                                                        <x-icon name="eye" class="text-muted opacity-50" />
                                                        <svg class="absolute -bottom-1 -right-1.5 h-3 w-3 text-amber-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3.3a1.7 1.7 0 0 1 3 0l6.3 11.2A1.7 1.7 0 0 1 16.3 17H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 3.3ZM10 7a.9.9 0 0 0-.9.9v3.4a.9.9 0 1 0 1.8 0V7.9A.9.9 0 0 0 10 7Zm0 7.6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                                    </span>
                                                </x-slot:icono>
                                                {{ $noPublicable }}
                                            </x-ayuda>
                                        </span>
                                    @elseif ($oferta->estado !== \App\Enums\EstadoOferta::Activa)
                                        <x-admin.boton-fila :action="route('admin.ofertas.publicar', $oferta)" icono="eye" color="sage"
                                                            :etiqueta="'Publicar la oferta '.$oferta->titulo"
                                                            titulo="¿Publicar «{{ $oferta->titulo }}»?"
                                                            mensaje="Revisa título, beneficio, descripción y fechas: se mostrará en la portada hasta «Válida hasta»."
                                                            boton="Sí, publicar" />
                                    @else
                                        <x-admin.boton-fila :action="route('admin.ofertas.ocultar', $oferta)" icono="eye-off" color="amber"
                                                            :etiqueta="'Ocultar la oferta '.$oferta->titulo"
                                                            titulo="¿Ocultar «{{ $oferta->titulo }}»?"
                                                            mensaje="Dejará de verse en la portada. Seguirá aquí para editarla o volver a publicarla."
                                                            boton="Sí, ocultar" />
                                    @endif
                                </x-admin.acciones-fila>
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state
                            cols="7"
                            title="No hay ofertas registradas todavía"
                            description="Crea promociones y descuentos especiales para tus pacientes."
                        />
                    @endforelse
                </x-admin.table>

                <x-pagination-summary :items="$ofertas" label="ofertas" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
