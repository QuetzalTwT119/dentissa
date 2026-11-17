@props([
    'minWidth' => 'min-w-[760px]',
])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => "w-full text-left text-sm {$minWidth}"]) }}>
        <thead class="border-b border-line bg-shell/50 text-caption font-semibold uppercase tracking-label text-ink-soft">
            {{ $head }}
        </thead>
        <tbody class="divide-y divide-line/70">
            {{ $slot }}
        </tbody>
    </table>
</div>
