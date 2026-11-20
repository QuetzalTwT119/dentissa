<x-app-layout>
    <x-slot name="header">
        @include('admin.tratamientos._encabezado', ['tratamiento' => null])
    </x-slot>

    @include('admin.tratamientos._formulario', [
        'tratamiento' => null,
        'accion' => route('admin.tratamientos.store'),
        'metodo' => 'post',
    ])
</x-app-layout>
