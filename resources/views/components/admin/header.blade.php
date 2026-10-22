@props([
    'badge' => null,
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        @if ($badge)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                    {{ $badge }}
                </span>
            </div>
        @endif
        <h2 class="mt-1 text-2xl font-bold tracking-tight text-[#2D2526]">
            {{ $title }}
        </h2>
        @if ($description)
            <p class="mt-0.5 text-xs text-[#8A7A7D]">{{ $description }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex items-center gap-2.5 self-start sm:self-auto">
            {{ $actions }}
        </div>
    @endif
</div>
