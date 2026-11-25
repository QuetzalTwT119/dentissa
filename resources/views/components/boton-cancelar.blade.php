{{--
    "Cancelar" de los formularios (25/11/2026): un solo enlace con aspecto de botón
    (antes un <button> dentro de un <a>, que los lectores de pantalla leían dos veces).

    Uso: <x-boton-cancelar :href="route('admin.casos.index')" />
--}}
@props(['href'])

<x-boton-enlace :href="$href" {{ $attributes }}>Cancelar</x-boton-enlace>
