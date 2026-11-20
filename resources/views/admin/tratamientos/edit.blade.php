<x-app-layout>
    <x-slot name="header">
        @include('admin.tratamientos._encabezado', ['tratamiento' => $tratamiento])
    </x-slot>

    @include('admin.tratamientos._formulario', [
        'tratamiento' => $tratamiento,
        'accion' => route('admin.tratamientos.update', $tratamiento),
        'metodo' => 'put',
    ])
</x-app-layout>
