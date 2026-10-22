@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-[#EAF4EE] border border-[#4E8D72]/20 px-4 py-3 font-medium text-xs text-[#4E8D72] shadow-sm']) }}>
        {{ $status }}
    </div>
@endif
