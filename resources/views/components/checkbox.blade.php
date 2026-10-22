@props(['disabled' => false, 'checked' => false])

<input
    @disabled($disabled)
    type="checkbox"
    {{ $checked ? 'checked' : '' }}
    {{ $attributes->merge(['class' => 'rounded-md border-[#e9dfdb] text-brand-600 shadow-sm focus:ring-brand-400 focus:ring-offset-1 transition']) }}
>
