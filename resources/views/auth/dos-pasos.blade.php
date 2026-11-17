<x-guest-layout>
    <div class="mb-8">
        <p class="eyebrow">Verificación en dos pasos</p>
        <h2 class="mt-3 text-title-1 text-ink">Un paso más</h2>
        <p class="mt-2 text-callout text-muted">Abre tu app de autenticación y escribe el código de 6 dígitos de Dentissa. Si no tienes el celular, usa uno de tus códigos de recuperación.</p>
    </div>

    <form method="POST" action="{{ route('dos-pasos.verificar') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="codigo" value="Código" />
            {{-- one-time-code: el celular puede proponer el código automáticamente. --}}
            <x-text-input id="codigo" class="mt-2 block w-full tracking-label" type="text" name="codigo" required autofocus
                          autocomplete="one-time-code" inputmode="text" maxlength="20" placeholder="123456" />
            <x-input-error :messages="$errors->get('codigo')" />
        </div>

        <x-primary-button class="w-full justify-center py-3">Verificar y entrar</x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 underline-offset-4 hover:underline">Volver a iniciar sesión</a>
    </p>
</x-guest-layout>
