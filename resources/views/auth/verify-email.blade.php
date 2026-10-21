<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-600">Verificación de cuenta</p>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-[#2D2526] sm:text-3xl">Verifica tu correo</h2>
        <p class="mt-2 text-sm leading-6 text-[#6b5e62]">
            ¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo electrónico haciendo clic en el enlace que te acabamos de enviar? Si no recibiste el correo, con gusto te enviaremos otro.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-[#4E8D72]/20 bg-[#EAF4EE] p-4 text-xs font-medium text-[#4E8D72]">
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

            <button type="submit" class="text-xs font-medium text-[#5c4f53] hover:text-brand-700 underline focus:outline-none focus:ring-2 focus:ring-brand-400">
                Cerrar sesión
            </button>
        </form>
    </div>
</x-guest-layout>
