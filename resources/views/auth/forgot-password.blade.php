<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow">Seguridad de cuenta</p>
        <h2 class="mt-3 text-title-1 text-ink">Recuperar contraseña</h2>
        <p class="mt-2 text-callout text-muted">
            ¿Olvidaste tu contraseña? No hay problema. Solo indícanos tu correo electrónico y te enviaremos un enlace para restablecerla.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-ink" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('login') }}" class="link text-center text-sm">← Volver a inicio de sesión</a>
            <x-primary-button>
                Enviar enlace de restablecimiento
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
