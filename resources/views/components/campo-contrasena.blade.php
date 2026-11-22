{{--
    Campo de contraseña con botón para mostrarla u ocultarla (22/11/2026).
    Antes solo el inicio de sesión lo tenía; al crear o cambiar una contraseña
    no se podía ver lo escrito y solo al guardar decía "no coincide".

    <x-campo-contrasena id="password" name="password" autocomplete="new-password" />

    Cualquier otro atributo (x-model, required…) pasa al <input>.
--}}
@props(['id', 'name', 'autocomplete' => 'new-password'])

<div x-data="{ visible: false }" class="relative mt-1.5">
    <x-text-input :id="$id" :name="$name" type="password" ::type="visible ? 'text' : 'password'" autocomplete="{{ $autocomplete }}"
                  {{ $attributes->merge(['class' => 'block w-full pr-12']) }} />
    <button type="button" x-on:click="visible = ! visible" aria-controls="{{ $id }}"
            :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" :title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-muted transition-colors hover:text-ink focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300">
        <x-icon name="eye" class="h-5 w-5" x-show="! visible" />
        <x-icon name="eye-off" class="h-5 w-5" x-show="visible" x-cloak />
    </button>
</div>
