{{--
    Oferta con la que se agendó la cita (21/11/2026): la doctora la ve en la
    agenda y la aplica al cobrar. No muestra nada si la cita no tiene oferta.
    <x-cita-oferta :cita="$cita" />
--}}
@props(['cita'])

@if ($cita->oferta)
    <span {{ $attributes->merge(['class' => 'mt-1 inline-flex max-w-full items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-800 ring-1 ring-inset ring-brand-200']) }}
          data-cita-oferta title="Aplicar al cobrar: {{ $cita->oferta->titulo }}">
        <span class="truncate">Oferta {{ $cita->oferta->etiquetaBeneficio() }} · {{ $cita->oferta->titulo }}</span>
    </span>
@endif
