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
            <x-slot:actions>
                <a href="{{ route('admin.ofertas.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva oferta
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>
                <x-admin.card-header
                    title="Promociones y beneficios"
                    description="Gestiona las promociones y descuentos vigentes de la clínica."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.ofertas.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nueva oferta</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

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
                                <span class="inline-flex items-center rounded-full bg-peach-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-peach-200">
                                    {{ $tipoLabel[$oferta->tipo->value] ?? $oferta->tipo->value }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $oferta->tratamiento->nombre ?? '—' }}</td>
                            <td class="px-6 py-4 text-ink-soft whitespace-nowrap">{{ $oferta->fecha_inicio }} al {{ $oferta->fecha_fin }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-cream px-2.5 py-0.5 text-xs font-semibold text-ink border border-line">
                                    {{ $oferta->cupo_total > 0 ? $oferta->cupos_utilizados.'/'.$oferta->cupo_total : 'Sin límite' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-admin.status-badge :active="$oferta->activa" />
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.ofertas.edit', $oferta) }}" title="Editar" aria-label="Editar oferta"
                                       class="rounded-xl p-2 text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                        <x-icon name="edit" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.ofertas.destroy', $oferta) }}"
                                          onsubmit="return confirm('¿Eliminar la oferta &quot;{{ $oferta->titulo }}&quot;? Esta acción no se puede deshacer.');">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" title="Eliminar" aria-label="Eliminar oferta"
                                                class="rounded-xl p-2 text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">
                                            <x-icon name="trash" />
                                        </button>
                                    </form>
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

                <x-pagination-summary :items="$ofertas" label="ofertas" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
