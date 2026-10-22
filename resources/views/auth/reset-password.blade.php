<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Seguridad de cuenta</p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[#2D2526] sm:text-3xl">Restablecer contraseña</h2>
        <p class="mt-2 text-sm leading-6 text-[#6b5e62]">Ingresa tu nueva contraseña para acceder a Dentissa.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-[#382d31]" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" class="text-[#382d31]" />
            <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" class="text-[#382d31]" />

            <x-text-input id="password_confirmation" class="block mt-1.5 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end pt-2">
            <x-primary-button>
                Restablecer contraseña
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
