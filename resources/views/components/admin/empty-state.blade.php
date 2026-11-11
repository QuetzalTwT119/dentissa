@props([
    'colspan' => 5,
    'title' => 'No se encontraron registros',
    'description' => null,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-6 py-14 text-center">
        <div class="mx-auto max-w-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center mb-4 rounded-2xl bg-peach-50 text-brand-600 ring-1 ring-inset ring-peach-200">
                @if (isset($icon))
                    {{ $icon }}
                @else
                    <svg class="h-6 w-6 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                @endif
            </div>
            <p class="font-serif text-lg text-ink">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-muted">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </td>
</tr>
