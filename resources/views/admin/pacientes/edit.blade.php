<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Expediente de Paciente"
            title="Editar paciente — {{ $paciente->nombre }} {{ $paciente->apellido }}"
            subtitle="Actualización de contacto y datos oficiales del expediente."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Tarjeta de Identificación Rápida -->
            <x-admin.card class="p-5 sm:p-8">
                <div class="flex items-center gap-4 pb-6 border-b border-line/80 lg:pb-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 text-base font-semibold text-brand-700 border border-brand-200 shadow-soft">
                        {{ strtoupper(substr($paciente->nombre, 0, 1) . substr($paciente->apellido, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-headline font-semibold text-ink">{{ $paciente->nombre }} {{ $paciente->apellido }}</h3>
                        <p class="text-xs text-muted">ID de paciente: #{{ str_pad($paciente->id, 5, '0', STR_PAD_LEFT) }} &bull; Consultas registradas: {{ $paciente->historialClinico()->count() }}</p>
                    </div>
                </div>

                <p class="mt-6 text-sm text-ink-soft lg:hidden">
                    Actualiza el nombre y los datos de contacto del paciente.
                </p>

                <form method="POST" action="{{ route('admin.pacientes.update', $paciente) }}" data-confirmar="Los cambios se aplicarán de inmediato." data-confirmar-titulo="¿Guardar los datos del paciente?" data-confirmar-boton="Sí, guardar" data-confirmar-tono="normal" class="formulario-panel mt-6 space-y-6 lg:mt-4">
                    @csrf
                    @method('put')

                    <x-form-obligatorios />
                    <!-- Sección: Datos Personales -->
                    <div class="col-completa rounded-xl bg-cream/60 p-4 sm:p-6 border border-line/70 space-y-4 lg:p-4">
                        <h4 class="text-xs font-semibold uppercase tracking-label text-muted flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            Datos Personales y Contacto
                        </h4>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label :required="true" for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full"
                                              value="{{ old('nombre', $paciente->nombre) }}" required maxlength="100" />
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label :required="true" for="apellido" value="Apellido" />
                                <x-text-input id="apellido" name="apellido" class="mt-1.5 block w-full"
                                              value="{{ old('apellido', $paciente->apellido) }}" required maxlength="100" />
                                <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                            </div>

                            <div>
                                {{-- Obligatorio (22/11/2026): por él se le avisa por WhatsApp y se le reconoce al agendar. --}}
                                <x-input-label :required="true" for="telefono" value="Teléfono celular" />
                                <x-text-input id="telefono" name="telefono" type="tel" inputmode="numeric" class="mt-1.5 block w-full"
                                              value="{{ old('telefono', $paciente->telefono) }}" maxlength="20" required
                                              placeholder="10 dígitos; +52 opcional" />
                                <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="email" value="Correo electrónico (opcional)" />
                                <x-text-input id="email" name="email" type="email" class="mt-1.5 block w-full"
                                              value="{{ old('email', $paciente->email) }}" maxlength="100" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    {{-- Sin CURP, RFC, clave de elector ni tutor (22/11/2026, decisión del usuario): no se usaban.
                         Lo ya guardado se conserva en la base; esta pantalla ya no lo pide ni lo cambia. --}}

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <x-boton-cancelar href="{{ route('admin.pacientes.index') }}" />
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
