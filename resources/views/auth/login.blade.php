<x-guest-layout>
    <div class="mb-8">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-brand-700 border border-brand-200">
            <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
            Acceso profesional
        </span>
        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-[#2D2526] sm:text-4xl">Iniciar sesión</h2>
        <p class="mt-2 text-sm leading-relaxed text-[#6b5e62]">Entra con tu cuenta para continuar con la gestión del consultorio.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" class="text-xs font-bold uppercase tracking-wider text-[#382d31]" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#8A7A7D]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                    </svg>
                </span>
                <input id="email" class="block w-full rounded-2xl border border-[#e9dfdb] bg-white pl-10 pr-4 py-3 text-sm font-medium text-[#2D2526] shadow-xs placeholder:text-[#8A7A7D]/60 focus:border-brand-500 focus:ring-2 focus:ring-brand-200" 
                       type="email" name="email" value="{{ old('email') }}" placeholder="nombre@consultorio.com" required autofocus autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600 font-medium" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" class="text-xs font-bold uppercase tracking-wider text-[#382d31]" />
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#8A7A7D]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <input id="password" class="block w-full rounded-2xl border border-[#e9dfdb] bg-white pl-10 pr-4 py-3 text-sm font-medium text-[#2D2526] shadow-xs placeholder:text-[#8A7A7D]/60 focus:border-brand-500 focus:ring-2 focus:ring-brand-200"
                       type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600 font-medium" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded-md border-[#e9dfdb] text-brand-600 shadow-xs focus:ring-brand-400" name="remember">
                <span class="text-xs font-medium text-[#5c4f53]">Recuérdame</span>
            </label>
            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-brand-700 hover:text-brand-800 transition underline-offset-4 hover:underline" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#d75078] to-[#bd3f64] px-6 py-4 text-sm font-bold text-white shadow-soft transition-all duration-200 hover:from-[#bd3f64] hover:to-[#a12a54] hover:shadow-md hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
            <span>Iniciar sesión</span>
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
        </button>
    </form>

    <div class="mt-8 border-t border-[#e9dfdb]/80 pt-5 text-center">
        <p class="inline-flex items-center gap-1.5 text-xs text-[#8A7A7D]">
            <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Tu información está protegida y solo es visible para usuarios autorizados.
        </p>
    </div>
</x-guest-layout>
