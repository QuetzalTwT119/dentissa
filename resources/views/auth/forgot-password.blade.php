<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Seguridad de cuenta</p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[#2D2526] sm:text-3xl">Recuperar contraseña</h2>
        <p class="mt-2 text-sm leading-6 text-[#6b5e62]">
            ¿Olvidaste tu contraseña? No hay problema. Solo indícanos tu correo electrónico y te enviaremos un enlace para restablecerla.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-[#382d31]" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline">← Volver a inicio de sesión</a>
            <x-primary-button>
                Enviar enlace de restablecimiento
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
