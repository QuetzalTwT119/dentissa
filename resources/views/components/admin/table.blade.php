{{--
    Tabla del panel ("Mixta"): cada tabla se comporta según su tamaño.

    - Corta (predeterminada): nunca se desplaza de lado. Desde 1024 px es una
      tabla normal; en celular y tableta cada fila se apila como tarjeta: la primera
      columna arriba, luego cada dato con el título de su columna y las
      acciones abajo (clases .tabla-apilable en app.css). Los títulos se copian
      de <thead> a cada celda con el componente Alpine "tablaApilable".
    - Larga (:larga="true"): para tablas cuyas columnas no caben en un
      celular (suma de anchos mínimos > ~800 px, por ejemplo Auditoría). Sigue
      siendo tabla, se desplaza de lado solo lo necesario, la primera columna
      queda fija y aparece "Desliza para ver más columnas" solo si hace falta.
      $minWidth fija ese ancho mínimo.
--}}
@props([
    'minWidth' => 'min-w-[760px]',
    'larga' => false,
])

@if ($larga)
    <div class="tabla-larga tabla-panel" x-data="tablaLarga" :data-desborda="desborda" :data-desplazada="desplazada">
        <p x-cloak x-show="desborda" class="flex items-center gap-1.5 border-b border-line/70 px-4 py-2 text-xs text-muted sm:px-6" aria-hidden="true">
            Desliza para ver más columnas
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
        </p>
        <div x-ref="desplazable" x-on:scroll.passive="medir()" class="overflow-x-auto overscroll-x-contain"
             :tabindex="desborda ? 0 : null" :role="desborda ? 'region' : null" :aria-label="desborda ? 'Tabla con más columnas; se puede desplazar de lado' : null">
            <table {{ $attributes->merge(['class' => "w-full text-left text-sm {$minWidth}"]) }}>
                <thead class="border-b border-line bg-shell/50 text-caption font-semibold uppercase tracking-label text-ink-soft">
                    {{ $head }}
                </thead>
                <tbody class="divide-y divide-line/70">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="tabla-panel" x-data="tablaApilable">
        <table {{ $attributes->merge(['class' => 'tabla-apilable w-full text-left text-sm']) }}>
            <thead class="border-b border-line bg-shell/50 text-caption font-semibold uppercase tracking-label text-ink-soft">
                {{ $head }}
            </thead>
            <tbody class="divide-y divide-line/70">
                {{ $slot }}
            </tbody>
        </table>
    </div>
@endif
