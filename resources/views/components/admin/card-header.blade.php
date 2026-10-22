@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'border-b border-[#e9dfdb]/80 p-6 bg-gradient-to-r from-white via-white to-[#FAF6F6]/50']) }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        @if ($title || $description)
            <div>
                @if ($title)
                    <h3 class="text-base font-bold text-[#2D2526]">{{ $title }}</h3>
                @endif
                @if ($description)
                    <p class="text-sm text-[#6b5e62]">{{ $description }}</p>
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
        <div class="mt-5 pt-4 border-t border-[#e9dfdb]/70">
            {{ $extra }}
        </div>
    @endif
</div>
