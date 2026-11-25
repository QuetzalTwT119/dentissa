{{--
    Enlace con el aspecto de x-secondary-button (25/11/2026). Reemplaza el patrón
    de un botón secundario metido dentro de un enlace: un control dentro de otro es
    HTML inválido y los lectores de pantalla lo anunciaban dos veces.

    Uso: <x-boton-enlace :href="route('admin.reportes.pdf')">Descargar PDF</x-boton-enlace>
--}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-line bg-white px-5 py-2.5 text-sm font-semibold text-ink shadow-xs transition-[background-color,border-color,color,transform] duration-150 ease-out hover:border-peach-300 hover:text-brand-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:ring-offset-2']) }}>
    {{ $slot }}
</a>
