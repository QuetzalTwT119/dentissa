<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-rose-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wider hover:bg-rose-700 active:bg-rose-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
