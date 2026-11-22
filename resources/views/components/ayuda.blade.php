{{--
    Ícono con explicación (21/11/2026): un símbolo junto a un dato que, al pasar
    el mouse, enfocarlo con el teclado o tocarlo en el celular, muestra un texto
    corto. Sustituye frases largas en tablas. El globo usa posición fija para que
    no lo corte el desplazamiento de las tablas; se cierra con Esc, al tocar
    fuera o al desplazar la página.

    <x-ayuda tono="aviso" etiqueta="Tratamiento inactivo">La oferta no se muestra…</x-ayuda>

    Props:
      - etiqueta: nombre corto para lectores de pantalla (y el título del globo).
      - tono: aviso (⚠ ámbar, algo que revisar) | info (ⓘ amarillo con la "i" oscura,
        solo explica; 22/11/2026, antes gris). La "i" oscura mantiene el contraste.
--}}
@props(['etiqueta', 'tono' => 'info'])

@php
    $color = $tono === 'aviso'
        ? 'text-amber-600 hover:bg-amber-50 focus-visible:ring-amber-300'
        : 'hover:bg-amber-50 focus-visible:ring-amber-300';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex align-middle']) }} data-ayuda
      x-data="{
          abierto: false,
          estilo: '',
          abrir() {
              const r = this.$refs.boton.getBoundingClientRect();
              const ancho = Math.min(272, window.innerWidth - 32);
              const izquierda = Math.max(16, Math.min(r.left + r.width / 2 - ancho / 2, window.innerWidth - ancho - 16));
              // Abajo si cabe (el globo mide ~120 px con 3 renglones); si no, arriba.
              const abajo = window.innerHeight - r.bottom > 180;
              this.estilo = `width:${ancho}px;left:${izquierda}px;` + (abajo ? `top:${r.bottom + 8}px` : `bottom:${window.innerHeight - r.top + 8}px`);
              this.abierto = true;
          },
          cerrar() { this.abierto = false },
      }"
      x-on:keydown.escape.window="cerrar()"
      x-on:scroll.window.passive="cerrar()"
      x-on:resize.window="cerrar()">
    <button type="button" x-ref="boton"
            class="grid h-11 w-11 place-items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 lg:h-7 lg:w-7 {{ $color }}"
            aria-label="{{ $etiqueta }}: ver explicación"
            x-bind:aria-expanded="abierto.toString()"
            x-on:mouseenter="abrir()" x-on:mouseleave="cerrar()"
            x-on:focus="abrir()" x-on:blur="cerrar()"
            x-on:click.prevent="abierto ? cerrar() : abrir()"
            x-on:click.outside="cerrar()">
        @if ($tono === 'aviso')
            <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.5 3.3a1.7 1.7 0 0 1 3 0l6.3 11.2A1.7 1.7 0 0 1 16.3 17H3.7a1.7 1.7 0 0 1-1.5-2.5L8.5 3.3ZM10 7a.9.9 0 0 0-.9.9v3.4a.9.9 0 1 0 1.8 0V7.9A.9.9 0 0 0 10 7Zm0 7.6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
        @else
            <svg class="group/ayuda h-[18px] w-[18px]" viewBox="0 0 20 20" aria-hidden="true" data-ayuda-info><circle cx="10" cy="10" r="8" class="fill-amber-400"/><path d="M11 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a1 1 0 0 0 0 2v3a1 1 0 0 0 1 1h1a1 1 0 1 0 0-2v-3a1 1 0 0 0-1-1H9Z" class="fill-amber-950"/></svg>
        @endif
    </button>
    <span x-show="abierto" x-cloak role="tooltip" x-bind:style="estilo"
          x-transition:enter="transition-opacity duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
          class="pointer-events-none fixed z-50 rounded-xl border border-line bg-white px-3.5 py-2.5 text-left text-xs font-normal normal-case leading-relaxed tracking-normal text-ink-soft shadow-lg whitespace-normal">
        <span class="mb-0.5 block font-semibold text-ink">{{ $etiqueta }}</span>
        {{ $slot }}
    </span>
</span>
