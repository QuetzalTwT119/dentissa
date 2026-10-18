<x-guest-layout>
    <div class="mb-7">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Panel de administración</p>
        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900 sm:text-4xl">Iniciar sesión</h2>
        <p class="mt-2 text-sm leading-6 text-gray-600">Entra con tu cuenta para continuar con la gestión del consultorio.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-800" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-sm font-semibold text-gray-700" />
            <x-text-input id="email" class="mt-2 block min-h-12 w-full rounded-xl border-gray-200 bg-gray-50/70 px-4 text-sm shadow-sm transition placeholder:text-gray-400 focus:border-brand-500 focus:bg-white focus:ring-brand-500" type="email" name="email" :value="old('email')" placeholder="nombre@consultorio.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" class="text-sm font-semibold text-gray-700" />

            <x-text-input id="password" class="mt-2 block min-h-12 w-full rounded-xl border-gray-200 bg-gray-50/70 px-4 text-sm shadow-sm transition focus:border-brand-500 focus:bg-white focus:ring-brand-500"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm text-red-600" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                <span class="text-sm text-gray-600">Recuérdame</span>
            </label>
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm font-medium text-brand-700 underline-offset-4 hover:text-brand-800 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-brand-200 transition hover:bg-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 active:translate-y-px">
            Iniciar sesión
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
        </button>
    </form>

    <p class="mt-7 border-t border-gray-100 pt-5 text-center text-xs leading-5 text-gray-500">Tu información está protegida y solo es visible para usuarios autorizados.</p>
</x-guest-layout>
