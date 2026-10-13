@php
$pregunta ??= null;
$valor = fn (string $campo, $default = '') => old($campo, $pregunta?->{$campo} ?? $default);
@endphp

<div>
    <x-input-label for="pregunta" value="Pregunta" />
    <x-text-input id="pregunta" name="pregunta" class="mt-1 block w-full" :value="$valor('pregunta')" required autofocus />
    <x-input-error :messages="$errors->get('pregunta')" class="mt-2" />
</div>

<div>
    <x-input-label for="respuesta" value="Respuesta" />
    <textarea id="respuesta" name="respuesta" rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('respuesta') }}</textarea>
    <x-input-error :messages="$errors->get('respuesta')" class="mt-2" />
</div>

<div>
    <x-input-label for="variantes" value="Otras formas de preguntar (opcional)" />
    <textarea id="variantes" name="variantes" rows="4" maxlength="4000"
              aria-describedby="variantes-ayuda"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ $valor('variantes') }}</textarea>
    <p id="variantes-ayuda" class="mt-1 text-sm text-gray-600">
        Escribe una forma alternativa por línea, por ejemplo sinónimos o maneras comunes de expresar la misma duda.
        No agregues aquí información nueva a la respuesta.
    </p>
    <x-input-error :messages="$errors->get('variantes')" class="mt-2" />
</div>

<div>
    <x-input-label for="orden" value="Orden (menor primero)" />
    <x-text-input id="orden" name="orden" type="number" min="0" class="mt-1 block w-full" :value="$valor('orden', 0)" />
    <x-input-error :messages="$errors->get('orden')" class="mt-2" />
</div>

<div class="flex items-center">
    <x-checkbox id="activa" name="activa" value="1" :checked="$valor('activa', true)" />
    <x-input-label for="activa" value="Visible en el sitio público" class="ml-2" />
</div>
