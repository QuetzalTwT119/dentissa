{{--
    Aviso rojo de la historia clínica (22/11/2026): alergias, condiciones de
    riesgo, embarazo y reacción previa a la anestesia, para verlos ANTES de
    atender. Sale en el expediente y al registrar o editar una consulta.
    Sin historia clínica o sin nada de riesgo, no muestra nada.

    <x-aviso-clinico :historia="$paciente->historiaClinica" />
--}}
@props(['historia' => null])

@php $alertas = $historia?->alertas() ?? []; @endphp

@if ($alertas !== [])
    <div role="alert" {{ $attributes->merge(['class' => 'rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-rose-900 sm:px-5']) }} data-aviso-clinico>
        <p class="flex items-center gap-2 text-sm font-semibold">
            <svg class="h-5 w-5 shrink-0 text-rose-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3.3a1.7 1.7 0 0 1 3 0l6.3 11.2A1.7 1.7 0 0 1 16.3 17H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 3.3ZM10 7a.9.9 0 0 0-.9.9v3.4a.9.9 0 1 0 1.8 0V7.9A.9.9 0 0 0 10 7Zm0 7.6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
            Antes de atender, ten en cuenta:
        </p>
        <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 pl-7 text-sm">
            @foreach ($alertas as $alerta)
                <li class="list-disc">{{ $alerta }}</li>
            @endforeach
        </ul>
    </div>
@endif
