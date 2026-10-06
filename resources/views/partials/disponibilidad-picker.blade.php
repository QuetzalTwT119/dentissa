{{-- Espera un x-data padre con: idTratamiento, fecha, hora, horarios, loadingHorarios --}}
<template x-if="!idTratamiento || !fecha">
    <p class="text-sm text-gray-500">Selecciona un tratamiento y una fecha para ver los horarios disponibles.</p>
</template>

<template x-if="idTratamiento && fecha && loadingHorarios">
    <p class="text-sm text-gray-500">Cargando horarios disponibles…</p>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length === 0">
    <p class="text-sm text-red-600">No hay horarios disponibles ese día. Prueba con otra fecha.</p>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length > 0">
    <div class="flex flex-wrap gap-2">
        <template x-for="horario in horarios" :key="horario">
            <button type="button"
                    @click="hora = horario"
                    :class="hora === horario ? 'border-brand-600 bg-brand-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                    class="rounded-md border px-3 py-1.5 text-sm"
                    x-text="horario"></button>
        </template>
    </div>
</template>
