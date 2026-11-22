{{--
    Opción elegible con el diseño global .opcion (21/11/2026). Es un radio oculto
    dentro de un <label>, así funciona con teclado, lectores de pantalla y sin JS.

    <x-opcion name="beneficio" value="gratis" modelo="beneficio" :checked="true" :desmarcable="true">Gratis</x-opcion>

    Props:
      - name, value: los del <input type="radio">.
      - checked: elegida al cargar.
      - modelo: variable de Alpine (x-model) que guarda la elección.
      - desmarcable: tocar la elegida la quita (deja la variable vacía).
      - requerido: el grupo es obligatorio (el aviso de error sale al final del <fieldset>).
--}}
@props(['name', 'value', 'checked' => false, 'modelo' => null, 'desmarcable' => false, 'requerido' => false])

<label {{ $attributes->merge(['class' => 'opcion']) }}>
    <input type="radio" name="{{ $name }}" value="{{ $value }}" class="sr-only" @checked($checked) @required($requerido)
           @if ($modelo) x-model="{{ $modelo }}" @endif
           @if ($modelo && $desmarcable) x-on:click="if ({{ $modelo }} === '{{ $value }}') $nextTick(() => {{ $modelo }} = '')" @endif>
    {{ $slot }}
</label>
