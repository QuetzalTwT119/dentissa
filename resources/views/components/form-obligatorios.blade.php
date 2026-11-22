{{-- Nota al inicio de un formulario que tiene campos marcados con asterisco. --}}
<p {{ $attributes->merge(['class' => 'col-span-full text-xs text-muted']) }} data-nota-obligatorios>
    Los campos con <span class="font-semibold text-rose-700" aria-hidden="true">*</span><span class="sr-only">asterisco</span> son obligatorios.
</p>
