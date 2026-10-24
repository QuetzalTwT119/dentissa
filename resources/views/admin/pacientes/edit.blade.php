<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Expediente de Paciente"
            title="Editar paciente — {{ $paciente->nombre }} {{ $paciente->apellido }}"
            subtitle="Actualización de contacto y datos oficiales del expediente."
        >
            <x-slot:actions>
                <a href="{{ route('admin.pacientes.index') }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button" class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Volver al listado
                    </x-secondary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">
            
            <!-- Tarjeta de Identificación Rápida -->
            <div class="rounded-2xl border border-[#e9dfdb] bg-white p-6 shadow-soft sm:p-8">
                <div class="flex items-center gap-4 pb-6 border-b border-[#e9dfdb]/80">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-peach-100 text-base font-bold text-brand-700 border border-brand-200 shadow-soft">
                        {{ strtoupper(substr($paciente->nombre, 0, 1) . substr($paciente->apellido, 0, 1)) }}
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-[#2D2526]">{{ $paciente->nombre }} {{ $paciente->apellido }}</h3>
                        <p class="text-xs text-[#8A7A7D]">ID de paciente: #{{ str_pad($paciente->id, 5, '0', STR_PAD_LEFT) }} &bull; Consultas registradas: {{ $paciente->historialClinico()->count() }}</p>
                    </div>
                </div>

                <p class="mt-6 text-sm text-[#6b5e62]">
                    Actualiza los datos de contacto y, si hace falta, los identificadores oficiales o la relación familiar.
                </p>

                <form method="POST" action="{{ route('admin.pacientes.update', $paciente) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <!-- Sección: Datos Personales -->
                    <div class="rounded-xl bg-[#FAF6F6]/60 p-4 sm:p-6 border border-[#e9dfdb]/70 space-y-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#8A7A7D] flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                            Datos Personales y Contacto
                        </h4>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full"
                                              value="{{ old('nombre', $paciente->nombre) }}" required maxlength="100" />
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="apellido" value="Apellido" />
                                <x-text-input id="apellido" name="apellido" class="mt-1.5 block w-full"
                                              value="{{ old('apellido', $paciente->apellido) }}" required maxlength="100" />
                                <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="telefono" value="Teléfono (opcional)" />
                                <x-text-input id="telefono" name="telefono" type="tel" class="mt-1.5 block w-full"
                                              value="{{ old('telefono', $paciente->telefono) }}" maxlength="20"
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

                    <!-- Sección: Identificadores Oficiales -->
                    <div class="rounded-xl bg-white p-4 sm:p-6 border border-[#e9dfdb]/70 space-y-4">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#8A7A7D] flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-teal-600"></span>
                            Identificadores Oficiales y Tutoría
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <x-input-label for="curp" value="CURP" />
                                <x-text-input id="curp" name="curp" class="mt-1.5 block w-full uppercase" maxlength="18"
                                              value="{{ old('curp', $paciente->curp) }}" placeholder="Ej. GORP800101HDFRZL09"
                                              oninput="this.value = this.value.toUpperCase()" />
                                <x-input-error :messages="$errors->get('curp')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="rfc" value="RFC" />
                                <x-text-input id="rfc" name="rfc" class="mt-1.5 block w-full uppercase" maxlength="13"
                                              value="{{ old('rfc', $paciente->rfc) }}" placeholder="Ej. GORP800101AB1"
                                              oninput="this.value = this.value.toUpperCase()" />
                                <x-input-error :messages="$errors->get('rfc')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="ine_clave" value="Clave de elector (INE)" />
                                <x-text-input id="ine_clave" name="ine_clave" class="mt-1.5 block w-full uppercase" maxlength="18"
                                              value="{{ old('ine_clave', $paciente->ine_clave) }}"
                                              oninput="this.value = this.value.toUpperCase()" />
                                <x-input-error :messages="$errors->get('ine_clave')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tutor_id" value="Tutor o responsable (opcional)" />
                                <select id="tutor_id" name="tutor_id"
                                        class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                                    <option value="">Ninguno — paciente independiente</option>
                                    @foreach ($tutoresDisponibles as $t)
                                        <option value="{{ $t['id'] }}" @selected(old('tutor_id', $paciente->tutor_id) == $t['id'])>{{ $t['nombre'] }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tutor_id')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-4 border-t border-[#e9dfdb]">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.pacientes.index') }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
