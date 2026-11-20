@props(['value', 'required' => false])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-ink']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        {{-- Asterisco visible + texto para lectores de pantalla. --}}
        <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
    @endif
</label>
