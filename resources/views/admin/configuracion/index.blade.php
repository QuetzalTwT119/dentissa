{{--
    Configuración (22/11/2026): una sola página con todos los ajustes, uno debajo
    del otro, en lugar de un menú con una página por ajuste. Cada bloque tiene su
    propio formulario y su botón de guardar: guardar un teléfono nunca guarda
    horarios por accidente (y "Guardar" se activa solo en el bloque que cambió).
--}}
<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            title="Configuración"
            subtitle="Datos del consultorio, cédulas profesionales, horarios de atención y días u horas bloqueados."
        />
    </x-slot>

    <div class="panel-pagina py-6 sm:py-8">
        <div class="panel-contenido mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
            @include('admin.configuracion._consultorio')
            @include('admin.configuracion._cedulas')
            @include('admin.configuracion._horarios')
            @include('admin.configuracion._bloqueos')
        </div>
    </div>
</x-app-layout>