<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-line bg-white px-5 py-2.5 text-sm font-semibold text-ink shadow-xs transition-[background-color,border-color,color,transform] duration-150 ease-out hover:border-peach-300 hover:text-brand-700 active:scale-[0.97] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 disabled:active:scale-100']) }}>
    {{ $slot }}
</button>
