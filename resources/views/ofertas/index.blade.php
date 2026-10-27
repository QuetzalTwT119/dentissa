<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Beneficios y Descuentos"
            title="Ofertas vigentes"
            subtitle="Promociones clínicas activas y beneficios exclusivos para nuestros pacientes."
        />
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
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
                    <div class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft transition hover:shadow-md hover:border-[#f2b0a6] h-full flex flex-col justify-between">
                        <div>
                            @if ($oferta->imagen)
                                <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="h-48 w-full object-cover">
                            @else
                                <div class="flex h-48 w-full items-center justify-center bg-brand-50/70 border-b border-[#e9dfdb]/60 text-xs font-semibold text-brand-600">
                                    <span class="inline-flex items-center gap-1.5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm0 13a2 2 0 112-2H10a2 2 0 012 2z" />
                                        </svg>
                                        Promoción Dentissa
                                    </span>
                                </div>
                            @endif
                            <div class="p-5 sm:p-6">
                                <h3 class="font-bold text-[#2D2526] text-lg">{{ $oferta->titulo }}</h3>
                                @if ($oferta->tratamiento)
                                    <p class="mt-1 text-xs text-brand-700 font-semibold inline-flex items-center gap-1">
                                        <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                        Aplica a: {{ $oferta->tratamiento->nombre }}
                                    </p>
                                @endif
                                @if ($oferta->descripcion)
                                    <p class="mt-2.5 text-xs sm:text-sm text-[#6b5e62] leading-relaxed">{{ $oferta->descripcion }}</p>
                                @endif
                                @if ($valor)
                                    <div class="mt-4 inline-flex items-center rounded-xl bg-brand-50 px-3 py-1.5 text-sm font-bold text-brand-700 border border-brand-200">
                                        {{ $valor }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="px-5 sm:px-6 pb-5 pt-3 border-t border-[#e9dfdb]/60 text-xs text-[#8A7A7D] flex items-center justify-between">
                            <span>Vigente hasta</span>
                            <strong class="font-semibold text-[#2D2526]">{{ $oferta->fecha_fin }}</strong>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-[#e9dfdb] bg-white p-12 text-center text-sm text-[#8A7A7D] shadow-soft">
                        <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V4a2 2 0 10-2 2h2zm0 13a2 2 0 112-2H10a2 2 0 012 2z" />
                            </svg>
                        </div>
                        <h4 class="font-bold text-[#2D2526]">No hay ofertas vigentes por el momento</h4>
                        <p class="mt-1 text-xs">Consulta periódicamente o comunícate con el consultorio para conocer promociones de temporada.</p>
                    </div>
                @endforelse
            </div>
            <x-pagination-summary :items="$ofertas" label="ofertas" :integrated="false" />
        </div>
    </div>
</x-app-layout>
