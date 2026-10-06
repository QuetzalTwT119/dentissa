@props(['disabled' => false, 'checked' => false])

<input
    @disabled($disabled)
    type="checkbox"
    {{ $checked ? 'checked' : '' }}
    {{ $attributes->merge(['class' => 'rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500']) }}
>
