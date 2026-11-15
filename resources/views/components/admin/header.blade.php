@props([
    'badge' => null,
    'title',
    'description' => null,
    'subtitle' => null,
])

@php
    $desc = $description ?? $subtitle;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div>
        @if ($badge)
            <p class="eyebrow">{{ $badge }}</p>
        @endif
        <h2 class="mt-1.5 text-[1.7rem] leading-tight text-ink sm:text-3xl">
            {{ $title }}
        </h2>
        @if ($desc)
            <p class="mt-1 max-w-2xl text-sm text-muted">{{ $desc }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            {{ $actions }}
        </div>
    @endif
</div>
