<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Ofertas vigentes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto grid max-w-6xl gap-6 sm:grid-cols-2 sm:px-6 lg:grid-cols-3 lg:px-8">
            @forelse ($ofertas as $oferta)
                @php
                    $valor = null;
                    if ($oferta->precio_especial) {
                        $valor = 'Precio especial: $'.$oferta->precio_especial;
                    } elseif ($oferta->valor_descuento) {
                        $valor = $oferta->tipo_valor === 'porcentaje'
                            ? $oferta->valor_descuento.'% de descuento'
                            : '$'.$oferta->valor_descuento.' de descuento';
                    }
                @endphp
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @if ($oferta->imagen)
                        <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="h-40 w-full object-cover">
                    @endif
                    <div class="p-6">
                        <h3 class="font-semibold text-brand-600">{{ $oferta->titulo }}</h3>
                        @if ($oferta->tratamiento)
                            <p class="mt-1 text-xs text-gray-400">Aplica a: {{ $oferta->tratamiento->nombre }}</p>
                        @endif
                        @if ($oferta->descripcion)
                            <p class="mt-2 text-sm text-gray-600">{{ $oferta->descripcion }}</p>
                        @endif
                        @if ($valor)
                            <p class="mt-3 text-sm font-semibold text-brand-500">{{ $valor }}</p>
                        @endif
                        <p class="mt-2 text-xs text-gray-400">Vigente hasta {{ $oferta->fecha_fin }}</p>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white p-6 text-center text-sm text-gray-500 shadow-sm sm:rounded-lg">
                    No hay ofertas vigentes por el momento.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
