@php
$tiposConsulta = [
    'primera_vez' => 'Primera vez',
    'seguimiento' => 'Seguimiento',
    'urgencia' => 'Urgencia',
    'control' => 'Control',
];
$historial ??= null;
// Cita que se completó en la Agenda ("Registrar consulta", 22/11/2026).
$citaElegida ??= null;
$valor = function (string $campo, $default = '') use ($historial) {
    $actual = $historial?->{$campo} ?? $default;
    if ($actual instanceof \BackedEnum) {
        $actual = $actual->value;
    }
    return old($campo, $actual);
};
@endphp

{{-- Datos cortos en una fila; cada pregunta de texto ocupa su propio renglón (22/11/2026). --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <div>
        <x-input-label :required="true" for="fecha_consulta" value="Fecha de consulta (obligatoria)" />
        <x-text-input id="fecha_consulta" name="fecha_consulta" type="date" class="mt-1.5 block w-full" required
                      :value="$valor('fecha_consulta', $historial ? \Illuminate\Support\Carbon::parse($historial->fecha_consulta)->toDateString() : ($citaElegida?->fecha_hora?->toDateString() ?? now()->toDateString()))" />
        <x-input-error :messages="$errors->get('fecha_consulta')" class="mt-2" />
    </div>

    <div>
        <x-input-label :required="true" for="tipo_consulta" value="Tipo de consulta (obligatorio)" />
        <x-select id="tipo_consulta" name="tipo_consulta" required class="mt-1.5">
            @foreach ($tiposConsulta as $valorTipo => $etiqueta)
                <option value="{{ $valorTipo }}" @selected($valor('tipo_consulta', 'primera_vez') === $valorTipo)>{{ $etiqueta }}</option>
            @endforeach
        </x-select>
        <x-input-error :messages="$errors->get('tipo_consulta')" class="mt-2" />
    </div>

    @if ($citas->isNotEmpty())
        <div>
            <x-input-label for="id_cita" value="Cita relacionada (opcional)" />
            <x-select id="id_cita" name="id_cita" class="mt-1.5">
                <option value="">Ninguna</option>
                @foreach ($citas as $cita)
                    <option value="{{ $cita->id }}" @selected(old('id_cita', $historial?->id_cita ?? $citaElegida?->id) == $cita->id)>
                        {{ \Illuminate\Support\Carbon::parse($cita->fecha_hora)->translatedFormat('d M Y, H:i') }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('id_cita')" class="mt-2" />
        </div>
    @endif
</div>

<div>
    <x-input-label :required="true" for="motivo_consulta" value="Motivo de la consulta (obligatorio)" />
    <x-textarea id="motivo_consulta" name="motivo_consulta" rows="3" required class="mt-1.5">{{ $valor('motivo_consulta') }}</x-textarea>
    <x-input-error :messages="$errors->get('motivo_consulta')" class="mt-2" />
</div>

<div>
    <x-input-label for="diagnostico" value="Diagnóstico (opcional)" />
    <x-textarea id="diagnostico" name="diagnostico" rows="4" class="mt-1.5">{{ $valor('diagnostico') }}</x-textarea>
    <x-input-error :messages="$errors->get('diagnostico')" class="mt-2" />
</div>

<div>
    <x-input-label for="tratamiento_realizado" value="Tratamiento realizado (opcional)" />
    <x-textarea id="tratamiento_realizado" name="tratamiento_realizado" rows="4" class="mt-1.5">{{ $valor('tratamiento_realizado') }}</x-textarea>
    <x-input-error :messages="$errors->get('tratamiento_realizado')" class="mt-2" />
</div>

<div>
    <x-input-label for="medicamentos_recetados" value="Medicamentos recetados (opcional)" />
    <x-textarea id="medicamentos_recetados" name="medicamentos_recetados" rows="3" class="mt-1.5">{{ $valor('medicamentos_recetados') }}</x-textarea>
    <x-input-error :messages="$errors->get('medicamentos_recetados')" class="mt-2" />
</div>

<div>
    <x-input-label for="observaciones" value="Observaciones (opcional)" />
    <x-textarea id="observaciones" name="observaciones" rows="3" class="mt-1.5">{{ $valor('observaciones') }}</x-textarea>
    <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
</div>

<div class="rounded-xl border border-amber-200/80 bg-amber-50/50 p-4">
    <x-input-label for="notas_doctor" value="Notas privadas del doctor (opcional)" class="text-amber-950 font-semibold" />
    <p id="notas_doctor_ayuda" class="mt-1 text-xs text-amber-800">
        Solo el administrador puede consultar estas notas. No se incluyen en el PDF del historial.
    </p>
    <x-textarea id="notas_doctor" name="notas_doctor" rows="3" aria-describedby="notas_doctor_ayuda" class="mt-2">{{ $valor('notas_doctor') }}</x-textarea>
    <x-input-error :messages="$errors->get('notas_doctor')" class="mt-2" />
</div>
