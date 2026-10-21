@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-[#e9dfdb] bg-white text-[#2D2526] placeholder-[#8A7A7D] focus:border-brand-500 focus:ring-brand-400 rounded-xl shadow-sm text-sm transition']) }}>
