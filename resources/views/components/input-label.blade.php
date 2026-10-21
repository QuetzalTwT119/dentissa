@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-[#382d31]']) }}>
    {{ $value ?? $slot }}
</label>
