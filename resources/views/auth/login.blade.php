<x-guest-layout>
    <div class="mb-8">
        <p class="eyebrow">Acceso profesional</p>
        <h2 class="mt-3 text-title-1 text-ink">Hola de nuevo</h2>
        <p class="mt-2 text-callout text-muted">Entra con tu cuenta para continuar con la gestión del consultorio.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" placeholder="nombre@consultorio.com" required autofocus autocomplete="username" inputmode="email" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div x-data="{ visible: false }">
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="link inline-flex min-h-11 items-center text-sm sm:min-h-0" href="{{ route('password.request') }}">¿La olvidaste?</a>
                @endif
            </div>
            <div class="relative mt-2">
                <x-text-input id="password" class="block w-full pr-12" ::type="visible ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password" />
                <button type="button" @click="visible = !visible" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-muted transition-colors hover:text-ink" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" aria-controls="password">
                    <svg x-show="!visible" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5c4.64 0 8.57 3 9.96 7.18.07.2.07.43 0 .64C20.58 16.49 16.64 19.5 12 19.5c-4.64 0-8.57-3-9.96-7.18Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    <svg x-show="visible" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.22A10.48 10.48 0 0 0 2.04 12c1.29 4.34 5.31 7.5 10.07 7.5.99 0 1.95-.14 2.86-.4M6.23 6.23A10.45 10.45 0 0 1 12 4.5c4.76 0 8.77 3.16 10.07 7.5a10.52 10.52 0 0 1-4.29 5.77M6.23 6.23 3 3m3.23 3.23 3.65 3.65m7.89 7.89L21 21m-3.23-3.23-3.65-3.65m0 0a3 3 0 1 0-4.24-4.24m4.24 4.24L9.88 9.88" /></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label for="remember_me" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
            <x-checkbox id="remember_me" name="remember" />
            <span class="text-sm text-ink-soft">Mantener la sesión iniciada</span>
        </label>

        <button type="submit" class="btn-brand w-full py-3" data-loading-text="Entrando…">
            <span data-loading-label>Iniciar sesión</span>
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
        </button>
    </form>

    <p class="mt-8 flex items-start gap-2 border-t border-line/80 pt-5 text-footnote text-muted">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-sage-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        Tu información está protegida y solo es visible para usuarios autorizados.
    </p>
</x-guest-layout>
