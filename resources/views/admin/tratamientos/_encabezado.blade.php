{{-- Encabezado compacto del formulario de tratamiento (va dentro de <x-slot name="header">). --}}
@php
    $editando = $tratamiento !== null;
@endphp
<div class="flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        <nav aria-label="Ruta" class="text-sm text-muted">
            <a href="{{ route('admin.tratamientos.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Tratamientos</a>
            <span aria-hidden="true" class="mx-1.5">›</span>
            <span>{{ $editando ? 'Editar' : 'Nuevo' }}</span>
        </nav>
        <h1 class="mt-1 truncate text-title-2 text-ink">{{ $editando ? $tratamiento->nombre : 'Nuevo tratamiento' }}</h1>
    </div>
    <div class="flex items-center gap-2.5">
        <a href="{{ route('admin.tratamientos.index') }}" class="hidden sm:inline-block">
            <x-secondary-button type="button">Cancelar</x-secondary-button>
        </a>
        <x-primary-button form="form-tratamiento" class="hidden lg:inline-flex" data-guardar-arriba>{{ $editando ? 'Guardar cambios' : 'Crear tratamiento' }}</x-primary-button>
    </div>
</div>
