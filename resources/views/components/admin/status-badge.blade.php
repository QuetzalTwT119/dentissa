@props([
    'active' => false,
    'activeText' => 'Activo',
    'inactiveText' => 'Inactivo',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ' . ($active ? 'bg-sage-50 text-sage-700 ring-1 ring-inset ring-sage-500/20' : 'bg-shell text-ink-soft ring-1 ring-inset ring-line')]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'bg-sage-500' : 'bg-muted' }}" aria-hidden="true"></span>
    {{ $active ? $activeText : $inactiveText }}
</span>
