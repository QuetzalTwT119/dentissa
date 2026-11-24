{{--
    Registrar paciente sin cita (24/11/2026): quien llega sin cita, o para llenar
    su historia clínica antes. Mismos datos que Editar paciente. Al guardar se
    abre su expediente.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.pacientes.index')" volver-texto="Volver a Pacientes"
                        title="Nuevo paciente" subtitle="Registra a un paciente sin agendarle cita." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.pacientes.store') }}" data-confirmar="Se creará su expediente." data-confirmar-titulo="¿Registrar al paciente?" data-confirmar-boton="Sí, registrar" data-confirmar-tono="normal" class="formulario-panel space-y-6" data-nuevo-paciente>
                    @csrf

                    <x-form-obligatorios />
                    <div class="col-completa space-y-4 rounded-xl border border-line/70 bg-cream/60 p-4 sm:p-6 lg:p-4">
                        <h2 class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-label text-muted">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            Datos personales y contacto
                        </h2>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label :required="true" for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre')" required maxlength="100" autofocus autocomplete="off" />
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :required="true" for="apellido" value="Apellido" />
                                <x-text-input id="apellido" name="apellido" class="mt-1.5 block w-full" :value="old('apellido')" required maxlength="100" autocomplete="off" />
                                <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :required="true" for="telefono" value="Teléfono celular" />
                                <x-text-input id="telefono" name="telefono" type="tel" inputmode="numeric" class="mt-1.5 block w-full" :value="old('telefono')"
                                              maxlength="20" required placeholder="10 dígitos; +52 opcional" autocomplete="off" />
                                <p class="mt-1 text-xs text-muted">Por aquí le llegan sus confirmaciones y recordatorios de WhatsApp.</p>
                                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                                @if (session('paciente_existente'))
                                    <a href="{{ route('admin.pacientes.historial.index', session('paciente_existente')) }}" class="link mt-1 inline-block text-sm" data-paciente-existente>Abrir su expediente</a>
                                @endif
                            </div>
                            <div>
                                <x-input-label for="email" value="Correo electrónico (opcional)" />
                                <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full" :value="old('email')" maxlength="100" autocomplete="off" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Igual que en la agenda en línea: sin aceptar el aviso no se guardan sus datos. --}}
                    <div class="col-completa">
                        <label class="flex items-start gap-3 text-sm text-ink-soft">
                            <input type="checkbox" name="acepta_aviso" value="1" required @checked(old('acepta_aviso'))
                                   class="mt-0.5 h-5 w-5 shrink-0 rounded border-field text-brand-600 focus:ring-brand-300">
                            <span>El paciente leyó y aceptó el <a href="{{ route('privacidad.aviso') }}" target="_blank" rel="noopener" class="link">aviso de privacidad</a> (impreso o en línea). <span class="text-brand-600" aria-hidden="true">*</span></span>
                        </label>
                        <x-input-error :messages="$errors->get('acepta_aviso')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 border-t border-line pt-4">
                        <x-primary-button>Registrar paciente</x-primary-button>
                        <a href="{{ route('admin.pacientes.index') }}">
                            <x-secondary-button type="button">Cancelar</x-secondary-button>
                        </a>
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
