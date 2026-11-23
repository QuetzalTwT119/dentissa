{{--
    Botón global de regreso (23/11/2026): "← Volver a …" arriba del título de las
    pantallas que se abren desde otra (crear, editar, expediente, cuestionarios).
    Las pantallas principales del menú no lo llevan.
    Uso: <x-volver :href="route('admin.ofertas.index')" texto="Volver a Ofertas" />
    En <x-admin.header> basta con :volver="…" y volver-texto="…".
--}}
@props(['href', 'texto'])

<a href="{{ $href }}" data-volver
   {{ $attributes->merge(['class' => '-ml-2 inline-flex min-h-11 items-center gap-1.5 rounded-full px-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 hover:text-brand-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300']) }}>
    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
    {{ $texto }}
</a>
