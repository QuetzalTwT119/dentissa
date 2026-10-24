<section class="space-y-5">
    <header class="flex items-start gap-3.5">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-100" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3h.008M10.3 4.7 2.9 17.5a1.9 1.9 0 0 0 1.65 2.85h14.9a1.9 1.9 0 0 0 1.65-2.85L13.7 4.7a1.96 1.96 0 0 0-3.4 0Z" /></svg>
        </span>
        <div>
            <h2 class="text-lg font-bold tracking-tight text-[#2D2526]">Desactivar mi cuenta</h2>
            <p class="mt-1 text-sm leading-6 text-[#6b5e62]">Tu acceso se desactivará y ya no podrás iniciar sesión. La información de la clínica se conserva; una persona administradora podrá reactivar la cuenta.</p>
        </div>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Desactivar mi cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-[#2D2526]">
                ¿Seguro que quieres desactivar tu cuenta?
            </h2>

            <p class="mt-1 text-sm text-[#6b5e62]">
                Al confirmar, perderás el acceso a esta cuenta y no podrás iniciar sesión. Tus datos y los registros de la clínica se conservarán. Ingresa tu contraseña para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Contraseña" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full sm:w-3/4"
                    placeholder="Contraseña"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Desactivar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
