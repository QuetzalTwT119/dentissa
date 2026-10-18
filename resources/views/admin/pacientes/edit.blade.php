<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar paciente — {{ $paciente->nombre }} {{ $paciente->apellido }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <p class="text-sm text-gray-600">
                    Actualiza los datos de contacto y, si hace falta, los identificadores oficiales o la relación familiar.
                </p>

                <form method="POST" action="{{ route('admin.pacientes.update', $paciente) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-input-label for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" class="mt-1 block w-full"
                                          value="{{ old('nombre', $paciente->nombre) }}" required maxlength="100" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="apellido" value="Apellido" />
                            <x-text-input id="apellido" name="apellido" class="mt-1 block w-full"
                                          value="{{ old('apellido', $paciente->apellido) }}" required maxlength="100" />
                            <x-input-error :messages="$errors->get('apellido')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="telefono" value="Teléfono (opcional)" />
                            <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full"
                                          value="{{ old('telefono', $paciente->telefono) }}" maxlength="20"
                                          placeholder="10 dígitos; +52 opcional" />
                            <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Correo electrónico (opcional)" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                                          value="{{ old('email', $paciente->email) }}" maxlength="100" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-gray-200">
                    <p class="text-sm font-medium text-gray-700">Identificadores oficiales y relación familiar (opcionales)</p>

                    <div>
                        <x-input-label for="curp" value="CURP" />
                        <x-text-input id="curp" name="curp" class="mt-1 block w-full uppercase" maxlength="18"
                                      value="{{ old('curp', $paciente->curp) }}" placeholder="Ej. GORP800101HDFRZL09"
                                      oninput="this.value = this.value.toUpperCase()" />
                        <x-input-error :messages="$errors->get('curp')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rfc" value="RFC" />
                        <x-text-input id="rfc" name="rfc" class="mt-1 block w-full uppercase" maxlength="13"
                                      value="{{ old('rfc', $paciente->rfc) }}" placeholder="Ej. GORP800101AB1"
                                      oninput="this.value = this.value.toUpperCase()" />
                        <x-input-error :messages="$errors->get('rfc')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="ine_clave" value="Clave de elector (INE)" />
                        <x-text-input id="ine_clave" name="ine_clave" class="mt-1 block w-full uppercase" maxlength="18"
                                      value="{{ old('ine_clave', $paciente->ine_clave) }}"
                                      oninput="this.value = this.value.toUpperCase()" />
                        <x-input-error :messages="$errors->get('ine_clave')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tutor_id" value="Tutor o responsable (opcional)" />
                        <select id="tutor_id" name="tutor_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Ninguno — paciente independiente</option>
                            @foreach ($tutoresDisponibles as $t)
                                <option value="{{ $t['id'] }}" @selected(old('tutor_id', $paciente->tutor_id) == $t['id'])>{{ $t['nombre'] }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tutor_id')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
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
