@php
$tipoLabel = [
    'descuento' => 'Descuento',
    'paquete' => 'Paquete',
    'bonificacion' => 'Bonificación',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Promociones"
            title="Ofertas"
            subtitle="Promociones estacionales, paquetes dentales y beneficios vigentes."
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
                            <th class="px-6 py-3.5">Tipo</th>
                            <th class="px-6 py-3.5">Tratamiento</th>
                            <th class="px-6 py-3.5">Vigencia</th>
                            <th class="px-6 py-3.5 text-center">Cupos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($ofertas as $oferta)
                        <tr class="transition hover:bg-cream/60">
                            <td class="px-6 py-4 font-semibold text-ink whitespace-nowrap">{{ $oferta->titulo }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">
                                <x-chip class="bg-peach-50 text-brand-700 border border-peach-200">
                                    {{ $tipoLabel[$oferta->tipo->value] ?? $oferta->tipo->value }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $oferta->tratamiento->nombre ?? '—' }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $oferta->fecha_inicio }} al {{ $oferta->fecha_fin }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <x-chip class="bg-cream text-ink border border-line">
                                    {{ $oferta->cupo_total > 0 ? $oferta->cupos_utilizados.'/'.$oferta->cupo_total : 'Sin límite' }}
                                </x-chip>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$oferta->activa" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <x-admin.acciones-fila :editar="route('admin.ofertas.edit', $oferta)" :eliminar="route('admin.ofertas.destroy', $oferta)" que="la oferta" confirmar-titulo="¿Eliminar la oferta «{{ $oferta->titulo }}»?" />
                                </div>
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
