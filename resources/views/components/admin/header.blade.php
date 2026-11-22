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
        <h1 class="mt-1.5 text-title-2 text-ink">
            {{ $title }}
        </h1>
        @if ($desc)
            <p class="mt-1 max-w-2xl text-sm text-muted lg:[@media(max-height:820px)]:hidden">{{ $desc }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex flex-wrap items-center gap-2.5 self-start sm:self-auto">
            {{ $actions }}
        </div>
    @endif
</div>
