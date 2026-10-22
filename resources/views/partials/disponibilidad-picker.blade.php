{{-- Espera un x-data padre con: idTratamiento, fecha, hora, horarios, loadingHorarios --}}
<template x-if="!idTratamiento || !fecha">
    <p class="text-xs text-[#8A7A7D]">Selecciona un tratamiento y una fecha para ver los horarios disponibles.</p>
</template>

<template x-if="idTratamiento && fecha && loadingHorarios">
    <p class="text-xs font-medium text-brand-600 animate-pulse">Cargando horarios disponibles…</p>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length === 0">
    <p class="text-xs font-medium text-rose-600">No hay horarios disponibles ese día. Prueba con otra fecha.</p>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length > 0">
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
        <template x-for="horario in horarios" :key="horario">
            <button type="button"
                    @click="hora = horario"
                    :class="hora === horario ? 'border-brand-600 bg-brand-600 text-white shadow-sm' : 'border-[#e9dfdb] bg-white text-[#2D2526] hover:border-brand-300 hover:bg-brand-50/60'"
                    class="min-h-11 rounded-xl border px-2 py-2.5 text-center text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-brand-400"
                    x-text="horario"></button>
        </template>
    </div>
</template>
