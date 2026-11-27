{{--
    Texto largo recortado a pocas líneas con "Leer completo" (QA de testimonios,
    22/11/2026). Antes el testimonio largo quedaba cortado y solo se leía al
    pasar el mouse (imposible en celular), y se perdían los saltos de línea.

    El botón solo aparece si de verdad hay texto oculto. Sin JavaScript el
    texto queda recortado como antes (la clase está fija en el HTML).

    Uso: <x-texto-expandible :texto="$testimonio->comentario" lineas="3" comillas class="…" />
--}}
@props(['texto', 'lineas' => 5, 'comillas' => false])

@php
    // Clases literales para que Tailwind las incluya.
    $recorte = ['2' => 'line-clamp-2', '3' => 'line-clamp-3', '4' => 'line-clamp-4', '5' => 'line-clamp-5'][(string) $lineas] ?? 'line-clamp-5';
@endphp

{{-- init() con "this": asignar "cortado" desde x-init/$nextTick no avisaba a la vista.
     26/11/2026: se medía una sola vez, antes de cargar las letras y de que el carrusel
     diera su ancho final, y salía "Leer completo" en opiniones cortas. Ahora se vuelve
     a medir al cargar las letras y cada vez que cambia el tamaño del texto (sin medir
     mientras está abierto, porque entonces nunca está cortado). --}}
<div x-data="{
        abierto: false,
        cortado: false,
        medir() {
            if (this.abierto) return;
            const p = this.$refs.texto;
            this.cortado = p.scrollHeight > p.clientHeight + 1;
        },
        init() {
            this.$nextTick(() => this.medir());
            document.fonts?.ready.then(() => this.medir());
            if (window.ResizeObserver) new ResizeObserver(() => this.medir()).observe(this.$refs.texto);
        },
     }"
     {{ $attributes->merge(['class' => 'min-w-0']) }}>
    <p x-ref="texto" class="whitespace-pre-line break-words {{ $recorte }}" :class="abierto && '!line-clamp-none'">{{ $comillas ? '“'.$texto.'”' : $texto }}</p>
    <button type="button" x-show="cortado" x-cloak x-on:click="abierto = ! abierto" :aria-expanded="abierto.toString()"
            class="mt-2 text-sm font-semibold not-italic text-brand-700 underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 rounded"
            x-text="abierto ? 'Ver menos' : 'Leer completo'">Leer completo</button>
</div>
