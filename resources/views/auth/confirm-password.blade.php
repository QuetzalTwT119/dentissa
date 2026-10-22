<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Área protegida</p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[#2D2526] sm:text-3xl">Confirmar contraseña</h2>
        <p class="mt-2 text-sm leading-6 text-[#6b5e62]">
            Esta es un área segura de la aplicación. Confirma tu contraseña antes de continuar.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" class="text-[#382d31]" />

            <x-text-input id="password" class="block mt-1.5 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
