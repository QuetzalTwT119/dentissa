@php
$tipoLabel = [
    'descuento' => 'Descuento',
    'paquete' => 'Paquete',
    'bonificacion' => 'Bonificación',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
                    Ofertas
                </h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Promociones estacionales, paquetes dentales y beneficios vigentes.</p>
            </div>
            <a href="{{ route('admin.ofertas.create') }}" class="self-start sm:self-auto">
                <x-primary-button>Nueva oferta</x-primary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e9dfdb]/80 p-6">
                    <p class="text-sm text-[#6b5e62]">
                        Gestiona las promociones y descuentos vigentes de la clínica.
                    </p>

                    <a href="{{ route('admin.ofertas.create') }}">
                        <x-primary-button>Nueva oferta</x-primary-button>
                    </a>
                </div>

                <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
                        <tr>
                            <th class="px-6 py-3.5">Título</th>
                            <th class="px-6 py-3.5">Tipo</th>
                            <th class="px-6 py-3.5">Tratamiento</th>
                            <th class="px-6 py-3.5">Vigencia</th>
                            <th class="px-6 py-3.5">Cupos</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9dfdb]/60">
                        @forelse ($ofertas as $oferta)
                            <tr class="transition hover:bg-[#FAF6F6]/60">
                                <td class="px-6 py-4 font-medium text-[#2D2526]">{{ $oferta->titulo }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $tipoLabel[$oferta->tipo->value] ?? $oferta->tipo->value }}</td>
                                <td class="px-6 py-4 text-[#6b5e62]">{{ $oferta->tratamiento->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 text-[#6b5e62] whitespace-nowrap">{{ $oferta->fecha_inicio }} al {{ $oferta->fecha_fin }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-[#FAF6F6] px-2.5 py-0.5 text-xs font-medium text-[#2D2526] border border-[#e9dfdb]">
                                        {{ $oferta->cupo_total > 0 ? $oferta->cupos_utilizados.'/'.$oferta->cupo_total : 'Sin límite' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $oferta->activa ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                        {{ $oferta->activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1.5">
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
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-[#8A7A7D]">
                                    No hay ofertas registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <x-pagination-summary :items="$ofertas" label="ofertas" />
            </div>
        </div>
    </div>
</x-app-layout>
