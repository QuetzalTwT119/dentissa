@php
$testimonio ??= null;
$valor = function (string $campo, $default = '') use ($testimonio) {
    $actual = $testimonio?->{$campo} ?? $default;
    if ($actual instanceof \BackedEnum) {
        $actual = $actual->value;
    }
    return old($campo, $actual);
};
@endphp

<div>
    <x-input-label for="id_paciente" value="Paciente" />
    <select id="id_paciente" name="id_paciente" required
            class="min-h-11 mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Selecciona un paciente</option>
        @foreach ($pacientes as $paciente)
            <option value="{{ $paciente['id'] }}" @selected($valor('id_paciente') == $paciente['id'])>{{ $paciente['nombre'] }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('id_paciente')" class="mt-2" />
</div>

<div>
    <x-input-label for="calificacion" value="Calificación (1 a 5, opcional)" />
    <select id="calificacion" name="calificacion"
            class="min-h-11 mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Sin calificación</option>
        @foreach ([1, 2, 3, 4, 5] as $n)
            <option value="{{ $n }}" @selected((string) $valor('calificacion') === (string) $n)>{{ str_repeat('★', $n) }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('calificacion')" class="mt-2" />
</div>

<div>
    <x-input-label for="comentario" value="Comentario" />
    <textarea id="comentario" name="comentario" rows="4" required
              class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition placeholder:text-muted focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ $valor('comentario') }}</textarea>
    <x-input-error :messages="$errors->get('comentario')" class="mt-2" />
</div>

<div>
    <x-input-label for="estado" value="Estado" />
    <select id="estado" name="estado"
            class="min-h-11 mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="pendiente" @selected($valor('estado', 'pendiente') === 'pendiente')>Pendiente</option>
        <option value="aprobado" @selected($valor('estado', 'pendiente') === 'aprobado')>Aprobado (visible en el sitio público)</option>
        <option value="rechazado" @selected($valor('estado', 'pendiente') === 'rechazado')>Rechazado</option>
    </select>
    <x-input-error :messages="$errors->get('estado')" class="mt-2" />
</div>
