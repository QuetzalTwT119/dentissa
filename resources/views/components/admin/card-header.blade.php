@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'border-b border-line/80 px-5 py-5 sm:px-6 lg:py-3.5']) }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        @if ($title || $description)
            <div>
                @if ($title)
                    <h3 class="text-headline font-semibold text-ink">{{ $title }}</h3>
                @endif
                @if ($description)
                    {{-- En PC con poca altura se oculta: es ayuda secundaria y le quita espacio a la tabla. --}}
                    <p class="mt-0.5 text-sm text-muted lg:[@media(max-height:820px)]:hidden">{{ $description }}</p>
                @endif
            </div>
        @endif

        @if (isset($actions))
            <div class="flex flex-wrap items-center gap-3">
                {{ $actions }}
            </div>
        @else
            {{ $slot }}
        @endif
    </div>

    @if (isset($extra))
        <div class="mt-5 pt-4 border-t border-line/70 lg:mt-3 lg:pt-3">
            {{ $extra }}
        </div>
    @endif
</div>
