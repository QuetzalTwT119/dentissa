@php
$tiposConsulta = [
    'primera_vez' => 'Primera vez',
    'seguimiento' => 'Seguimiento',
    'urgencia' => 'Urgencia',
    'control' => 'Control',
];
$historial ??= null;
$valor = function (string $campo, $default = '') use ($historial) {
    $actual = $historial?->{$campo} ?? $default;
    if ($actual instanceof \BackedEnum) {
        $actual = $actual->value;
    }
    return old($campo, $actual);
};
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="fecha_consulta" value="Fecha de consulta (obligatoria)" />
        <x-text-input id="fecha_consulta" name="fecha_consulta" type="date" class="mt-1 block w-full" required
                      :value="$valor('fecha_consulta', $historial ? \Illuminate\Support\Carbon::parse($historial->fecha_consulta)->toDateString() : now()->toDateString())" />
        <x-input-error :messages="$errors->get('fecha_consulta')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="tipo_consulta" value="Tipo de consulta (obligatorio)" />
        <select id="tipo_consulta" name="tipo_consulta" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach ($tiposConsulta as $valorTipo => $etiqueta)
                <option value="{{ $valorTipo }}" @selected($valor('tipo_consulta', 'primera_vez') === $valorTipo)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo_consulta')" class="mt-2" />
    </div>
</div>

@if ($citas->isNotEmpty())
    <div>
        <x-input-label for="id_cita" value="Cita relacionada (opcional)" />
        <select id="id_cita" name="id_cita"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            <option value="">Ninguna</option>
            @foreach ($citas as $cita)
                <option value="{{ $cita->id }}" @selected(old('id_cita', $historial?->id_cita) == $cita->id)>
                    {{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->translatedFormat('d M Y, H:i') }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('id_cita')" class="mt-2" />
    </div>
@endif

<div>
    <x-input-label for="motivo_consulta" value="Motivo de la consulta (obligatorio)" />
    <textarea id="motivo_consulta" name="motivo_consulta" rows="2" required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('motivo_consulta') }}</textarea>
    <x-input-error :messages="$errors->get('motivo_consulta')" class="mt-2" />
</div>

<div>
    <x-input-label for="diagnostico" value="Diagnóstico (opcional)" />
    <textarea id="diagnostico" name="diagnostico" rows="2"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('diagnostico') }}</textarea>
    <x-input-error :messages="$errors->get('diagnostico')" class="mt-2" />
</div>

<div>
    <x-input-label for="tratamiento_realizado" value="Tratamiento realizado (opcional)" />
    <textarea id="tratamiento_realizado" name="tratamiento_realizado" rows="2"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('tratamiento_realizado') }}</textarea>
    <x-input-error :messages="$errors->get('tratamiento_realizado')" class="mt-2" />
</div>

<div>
    <x-input-label for="medicamentos_recetados" value="Medicamentos recetados (opcional)" />
    <textarea id="medicamentos_recetados" name="medicamentos_recetados" rows="2"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('medicamentos_recetados') }}</textarea>
    <x-input-error :messages="$errors->get('medicamentos_recetados')" class="mt-2" />
</div>

<div>
    <x-input-label for="observaciones" value="Observaciones (opcional)" />
    <textarea id="observaciones" name="observaciones" rows="2"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('observaciones') }}</textarea>
    <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
</div>

<div>
    <x-input-label for="notas_doctor" value="Notas privadas del doctor (opcional)" />
    <p id="notas_doctor_ayuda" class="mt-1 text-sm text-gray-500">
        Solo el administrador puede consultar estas notas. No se incluyen en el PDF del historial.
    </p>
    <textarea id="notas_doctor" name="notas_doctor" rows="2"
              aria-describedby="notas_doctor_ayuda"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('notas_doctor') }}</textarea>
    <x-input-error :messages="$errors->get('notas_doctor')" class="mt-2" />
</div>
