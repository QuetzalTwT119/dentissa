@props([
    'active' => false,
    'activeText' => 'Activo',
    'inactiveText' => 'Inactivo',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ' . ($active ? 'bg-[#EAF4EE] text-[#4E8D72] border border-[#4E8D72]/20' : 'bg-gray-100 text-gray-600 border border-gray-200')]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $active ? 'bg-[#4E8D72]' : 'bg-gray-400' }}" aria-hidden="true"></span>
    {{ $active ? $activeText : $inactiveText }}
</span>
