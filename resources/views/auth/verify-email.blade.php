<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow">Verificación de cuenta</p>
        <h2 class="mt-3 text-[2rem] leading-tight text-ink sm:text-[2.4rem]">Verifica tu correo</h2>
        <p class="mt-2 text-sm leading-6 text-muted">
            ¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo electrónico haciendo clic en el enlace que te acabamos de enviar? Si no recibiste el correo, con gusto te enviaremos otro.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-sage-500/20 bg-sage-50 p-4 text-xs font-medium text-sage-500">
            Se ha enviado un nuevo enlace de verificación al correo electrónico que registraste.
        </div>
    @endif

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                Reenviar correo de verificación
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-xs font-medium text-ink-soft hover:text-brand-700 underline focus:outline-none focus:ring-2 focus:ring-brand-400">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
