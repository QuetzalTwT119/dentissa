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
                            <th class="px-6 py-3.5 text-right lg:text-center">Acciones</th>
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
                                @php $colorEstado = ['pendiente' => 'bg-amber-50 text-amber-800 ring-amber-500/25', 'activa' => 'bg-sage-50 text-sage-700 ring-sage-500/20', 'inactiva' => 'bg-shell text-ink-soft ring-line'][$oferta->estado->value]; @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $colorEstado }}" data-estado-oferta="{{ $oferta->estado->value }}">{{ $oferta->estado->etiqueta() }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- Mismo grupo que Editar/Eliminar, sin separador (21/11/2026). --}}
                                <x-admin.acciones-fila :editar="route('admin.ofertas.edit', $oferta)" :eliminar="route('admin.ofertas.destroy', $oferta)" que="la oferta" confirmar-titulo="¿Eliminar la oferta «{{ $oferta->titulo }}»?">
                                    {{-- En revisión u oculta: ✓ publica. Publicada: ocultar (sigue aquí para reactivarla). --}}
                                    @if ($oferta->estado !== \App\Enums\EstadoOferta::Activa)
                                        <x-admin.boton-fila :action="route('admin.ofertas.publicar', $oferta)" icono="check" color="sage"
                                                            :etiqueta="'Publicar la oferta '.$oferta->titulo"
                                                            titulo="¿Publicar «{{ $oferta->titulo }}»?"
                                                            mensaje="Revisa título, beneficio, descripción y fechas: se mostrará en la portada hasta «Válida hasta»."
                                                            boton="Sí, publicar" />
                                    @else
                                        <x-admin.boton-fila :action="route('admin.ofertas.ocultar', $oferta)" icono="power" color="amber"
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
