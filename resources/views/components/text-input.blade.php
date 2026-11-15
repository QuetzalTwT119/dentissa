@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'min-h-11 rounded-xl border-line bg-white text-sm text-ink shadow-2xs placeholder:text-muted/80 transition-[border-color,box-shadow] duration-150 focus:border-brand-400 focus:ring-4 focus:ring-brand-500/15 disabled:bg-shell/60 disabled:text-muted']) }}>
