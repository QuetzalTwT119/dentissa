@php
$tipoLabel = [
    'descuento' => 'Descuento',
    'paquete' => 'Paquete',
    'bonificacion' => 'Bonificación',
];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Ofertas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-6">
                    <p class="text-sm text-gray-600">
                        Gestiona las promociones y descuentos vigentes de la clínica.
                    </p>

                    <a href="{{ route('admin.ofertas.create') }}">
                        <x-primary-button>Nueva oferta</x-primary-button>
                    </a>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="border-y border-gray-200 bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-3">Título</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Tratamiento</th>
                            <th class="px-6 py-3">Vigencia</th>
                            <th class="px-6 py-3">Cupos</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ofertas as $oferta)
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $oferta->titulo }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $tipoLabel[$oferta->tipo->value] ?? $oferta->tipo->value }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $oferta->tratamiento->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ $oferta->fecha_inicio }} al {{ $oferta->fecha_fin }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ $oferta->cupo_total > 0 ? $oferta->cupos_utilizados.'/'.$oferta->cupo_total : 'Sin límite' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="{{ $oferta->activa ? 'text-green-700' : 'text-gray-400' }}">
                                        {{ $oferta->activa ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <a href="{{ route('admin.ofertas.edit', $oferta) }}" title="Editar" aria-label="Editar oferta"
                                           class="rounded p-1.5 text-brand-600 transition hover:bg-brand-50 hover:text-brand-900">
                                            <x-icon name="edit" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.ofertas.destroy', $oferta) }}"
                                              onsubmit="return confirm('¿Eliminar la oferta &quot;{{ $oferta->titulo }}&quot;? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" title="Eliminar" aria-label="Eliminar oferta"
                                                    class="rounded p-1.5 text-red-600 transition hover:bg-red-50 hover:text-red-900">
                                                <x-icon name="trash" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No hay ofertas registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
