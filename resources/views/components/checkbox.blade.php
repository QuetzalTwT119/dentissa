@props(['disabled' => false, 'checked' => false])

<input
    @disabled($disabled)
    type="checkbox"
    {{ $checked ? 'checked' : '' }}
    {{ $attributes->merge(['class' => 'h-5 w-5 rounded-md border-field text-brand-600 shadow-2xs focus:ring-brand-400 focus:ring-offset-1']) }}
>
