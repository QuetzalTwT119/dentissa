{{-- Encabezado compacto del formulario de tratamiento (va dentro de <x-slot name="header">). --}}
@php
    $editando = $tratamiento !== null;
@endphp
<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <h1 class="truncate text-title-2 text-ink">{{ $editando ? $tratamiento->nombre : 'Nuevo tratamiento' }}</h1>
    </div>
    <div class="flex items-center gap-2.5">
        <x-boton-cancelar href="{{ route('admin.tratamientos.index') }}" class="hidden sm:inline-flex" />
        <x-primary-button form="form-tratamiento" class="hidden lg:inline-flex" data-guardar-arriba>{{ $editando ? 'Guardar cambios' : 'Crear tratamiento' }}</x-primary-button>
    </div>
</div>
