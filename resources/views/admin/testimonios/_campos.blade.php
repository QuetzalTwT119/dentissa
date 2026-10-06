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
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
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
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
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
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('comentario') }}</textarea>
    <x-input-error :messages="$errors->get('comentario')" class="mt-2" />
</div>

<div>
    <x-input-label for="estado" value="Estado" />
    <select id="estado" name="estado"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        <option value="pendiente" @selected($valor('estado', 'pendiente') === 'pendiente')>Pendiente</option>
        <option value="aprobado" @selected($valor('estado', 'pendiente') === 'aprobado')>Aprobado (visible en el sitio público)</option>
        <option value="rechazado" @selected($valor('estado', 'pendiente') === 'rechazado')>Rechazado</option>
    </select>
    <x-input-error :messages="$errors->get('estado')" class="mt-2" />
</div>
