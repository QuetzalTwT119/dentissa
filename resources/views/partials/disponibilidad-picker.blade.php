{{-- Espera un x-data padre con: idTratamiento, fecha, hora, horarios, loadingHorarios --}}
<template x-if="!idTratamiento || !fecha">
    <p class="rounded-2xl bg-cream px-4 py-5 text-center text-sm text-muted">Selecciona un tratamiento y una fecha para ver los horarios disponibles.</p>
</template>

<template x-if="idTratamiento && fecha && loadingHorarios">
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5" aria-busy="true"><span class="sr-only">Cargando horarios disponibles…</span><template x-for="i in 8" :key="i"><span class="h-12 animate-pulse rounded-xl bg-shell"></span></template></div>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length === 0">
    <p class="rounded-2xl bg-rose-50 px-4 py-5 text-center text-sm font-medium text-rose-700">No hay horarios disponibles ese día. Prueba con otra fecha.</p>
</template>

<template x-if="idTratamiento && fecha && !loadingHorarios && horarios.length > 0">
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-5">
        <template x-for="horario in horarios" :key="horario">
            <button type="button"
                    @click="hora = horario"
                    :class="hora === horario ? 'border-brand-600 bg-brand-600 text-white shadow-brand' : 'border-line bg-white text-ink hover:border-peach-300 hover:bg-peach-50/60'"
                    :aria-pressed="(hora === horario).toString()"
                    class="min-h-12 rounded-xl border px-2 py-2.5 text-center text-[0.95rem] font-semibold tabular-nums transition-[background-color,border-color,color,transform] duration-150 ease-out active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                    x-text="horario"></button>
        </template>
    </div>
</template>
