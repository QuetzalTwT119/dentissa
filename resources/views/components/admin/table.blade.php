@props([
    'minWidth' => 'min-w-[760px]',
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => "w-full text-left text-sm {$minWidth}"]) }}>
        <thead class="border-b border-[#e9dfdb] bg-[#FAF6F6] text-xs font-semibold uppercase tracking-wider text-[#6b5e62]">
            {{ $head }}
        </thead>
        <tbody class="divide-y divide-[#e9dfdb]/60">
            {{ $slot }}
        </tbody>
    </table>
</div>
