@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-sage-500/20 bg-sage-50 px-4 py-3 text-sm font-medium text-sage-700']) }}>
        {{ $status }}
    </div>
@endif
