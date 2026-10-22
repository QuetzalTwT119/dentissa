<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white border border-[#e9dfdb] rounded-xl font-semibold text-xs text-[#51464a] uppercase tracking-wider shadow-sm hover:bg-[#faf7f4] hover:text-brand-700 hover:border-brand-300 focus:outline-none focus:ring-2 focus:ring-brand-300 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
