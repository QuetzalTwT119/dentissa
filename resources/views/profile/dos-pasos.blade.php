<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('profile.edit')" volver-texto="Volver a Mi perfil"
            badge="Seguridad de la cuenta"
            title="Verificación en dos pasos"
            subtitle="Además de tu contraseña, al entrar se pedirá un código de tu celular."
        />
    </x-slot>

    <div class="panel-pagina py-7 sm:py-10">
        <div class="panel-contenido mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-alert type="success" :message="session('status')" />
            @endif

            @if ($codigos)
                {{-- Solo se muestran una vez: en la base se guarda únicamente su hash. --}}
                <section class="card-boutique border-amber-200 p-5 sm:p-7" aria-label="Códigos de recuperación" data-codigos-recuperacion>
                    <h3 class="text-headline font-semibold text-ink">Guarda tus códigos de recuperación</h3>
                    <p class="mt-2 text-callout text-muted">Si pierdes el celular, cada código sirve <strong>una sola vez</strong> para entrar. Guárdalos en un lugar seguro (impresos o en un gestor de contraseñas). <strong>No se volverán a mostrar.</strong></p>
                    <ul class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm sm:grid-cols-4">
                        @foreach ($codigos as $codigo)
                            <li class="rounded-xl bg-cream px-3 py-2 text-center tracking-label text-ink">{{ $codigo }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (! $usuario->tieneDosPasos())
                <section class="card-boutique p-5 sm:p-7" aria-label="Activar verificación en dos pasos">
                    @if ($obligatoria)
                        <p class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-callout text-amber-900">
                            Como administrador puedes ver historiales clínicos: la verificación en dos pasos es <strong>obligatoria</strong> para usar el panel.
                        </p>
                    @endif

                    <ol class="space-y-6 text-callout text-ink-soft">
                        <li>
                            <p class="font-semibold text-ink">1. Instala una app de autenticación gratuita</p>
                            <p>Google Authenticator o Microsoft Authenticator, en tu celular.</p>
                        </li>
                        <li>
                            <p class="font-semibold text-ink">2. Escanea este código QR con la app</p>
                            <div class="mt-3 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                                <div class="rounded-2xl border border-line bg-white p-3" aria-label="Código QR para la app de autenticación" role="img">{!! $qr['svg'] !!}</div>
                                <div class="min-w-0">
                                    <p>¿No puedes escanear? Escribe esta clave en la app:</p>
                                    <p class="mt-1 break-all font-mono text-base tracking-label text-ink" data-clave-manual>{{ $qr['clave'] }}</p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <p class="font-semibold text-ink">3. Escribe el código de 6 dígitos que muestra la app</p>
                            <form method="POST" action="{{ route('dos-pasos.confirmar') }}" class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start">
                                @csrf
                                <div class="sm:w-48">
                                    <x-text-input id="codigo" name="codigo" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="10" required class="block w-full tracking-label" placeholder="123456" aria-label="Código de 6 dígitos" />
                                    <x-input-error :messages="$errors->get('codigo')" />
                                </div>
                                <x-primary-button class="justify-center py-3">Activar</x-primary-button>
                            </form>
                        </li>
                    </ol>
                </section>
            @else
                <section class="card-boutique p-5 sm:p-7" aria-label="Estado de la verificación">
                    <p class="text-sm text-ink-soft"><span class="rounded-full bg-sage-50 px-2.5 py-1 text-xs font-semibold text-sage-700">Activa</span>
                        desde el {{ $usuario->dos_pasos_confirmado_en->locale('es')->translatedFormat('j \d\e F \d\e Y') }}. Te quedan <strong>{{ $restantes }}</strong> códigos de recuperación.</p>

                    <form method="POST" action="{{ route('dos-pasos.regenerar') }}" data-confirmar="Los códigos anteriores dejarán de servir." data-confirmar-titulo="¿Generar códigos nuevos?" data-confirmar-boton="Sí, generar" data-confirmar-tono="normal" class="mt-6 space-y-3">
                        @csrf
                        <p class="text-sm font-semibold text-ink">Generar códigos de recuperación nuevos</p>
                        <p class="text-sm text-muted">Los anteriores dejarán de servir.</p>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <div class="sm:w-64">
                                <x-text-input name="current_password" type="password" autocomplete="current-password" required class="block w-full" placeholder="Tu contraseña actual" aria-label="Contraseña actual para generar códigos" />
                                <x-input-error :messages="$errors->get('current_password')" />
                            </div>
                            <x-secondary-button type="submit" class="justify-center py-3">Generar códigos</x-secondary-button>
                        </div>
                    </form>

                    @unless ($obligatoria)
                        <form method="POST" action="{{ route('dos-pasos.desactivar') }}" class="mt-8 space-y-3 border-t border-line pt-6" data-confirmar="Tu cuenta quedará protegida solo con la contraseña." data-confirmar-titulo="¿Desactivar la verificación en dos pasos?" data-confirmar-boton="Sí, desactivar" data-confirmar-tono="peligro">
                            @csrf
                            @method('DELETE')
                            <p class="text-sm font-semibold text-ink">Desactivar</p>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                                <x-text-input name="current_password" type="password" autocomplete="current-password" required class="block w-full sm:w-64" placeholder="Tu contraseña actual" aria-label="Contraseña actual para desactivar" />
                                <x-danger-button class="justify-center py-3">Desactivar</x-danger-button>
                            </div>
                        </form>
                    @endunless
                </section>
            @endif

        </div>
    </div>
</x-app-layout>
