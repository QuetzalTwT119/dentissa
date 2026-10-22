<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">
            Ofertas vigentes
        </h2>
    </x-slot>

    <div class="py-10">
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
                <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft transition hover:shadow-md hover:border-[#f2b0a6]">
                    @if ($oferta->imagen)
                        <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="h-44 w-full object-cover">
                    @else
                        <div class="flex h-44 w-full items-center justify-center bg-brand-50 text-xs font-semibold text-brand-400">
                            Pendiente de imagen
                        </div>
                    @endif
                    <div class="p-6">
                        <h3 class="font-semibold text-[#2D2526] text-lg">{{ $oferta->titulo }}</h3>
                        @if ($oferta->tratamiento)
                            <p class="mt-1 text-xs text-brand-700 font-medium">Aplica a: {{ $oferta->tratamiento->nombre }}</p>
                        @endif
                        @if ($oferta->descripcion)
                            <p class="mt-2 text-sm text-[#6b5e62] leading-relaxed">{{ $oferta->descripcion }}</p>
                        @endif
                        @if ($valor)
                            <p class="mt-3 text-sm font-semibold text-brand-600">{{ $valor }}</p>
                        @endif
                        <p class="mt-3 text-xs text-[#8A7A7D]">Vigente hasta {{ $oferta->fecha_fin }}</p>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-[#e9dfdb] bg-white p-12 text-center text-sm text-[#8A7A7D] shadow-soft">
                    <p class="font-medium">No hay ofertas vigentes por el momento.</p>
                    <p class="mt-1 text-xs text-[#8A7A7D]">Consulta periódicamente o comunícate con el consultorio para conocer promociones de temporada.</p>
                </div>
            @endforelse
        </div>
        <x-pagination-summary :items="$ofertas" label="ofertas" :integrated="false" />
    </div>
</x-app-layout>
