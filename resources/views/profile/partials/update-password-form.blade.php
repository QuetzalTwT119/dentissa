<section>
    <header class="flex items-start gap-3.5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-cream text-brand-600 ring-1 ring-line" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v2"/></svg>
        </span>
        <div>
            <h2 class="text-title-3 text-ink">Actualizar contraseña</h2>
            <p class="mt-1 text-callout text-ink-soft">Usa una contraseña larga y única para proteger el acceso a tu cuenta.</p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" data-confirmar="Se cerrarán tus sesiones en otros dispositivos." data-confirmar-titulo="¿Cambiar tu contraseña?" data-confirmar-boton="Sí, cambiar" data-confirmar-tono="normal" class="mt-6 grid grid-cols-1 gap-x-5 gap-y-5 md:grid-cols-2">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Contraseña actual" />
            <x-campo-contrasena id="update_password_current_password" name="current_password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <x-contrasena-nueva class="md:col-span-2" prefijo="update_password_" :errores="$errors->updatePassword" />

        <div class="flex flex-wrap items-center gap-4 md:col-span-2">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-ink-soft"
                role="status"
                >Contraseña actualizada.</p>
            @endif
        </div>
    </form>
</section>
