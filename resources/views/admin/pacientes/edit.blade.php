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
                    Identificadores oficiales y relación familiar del paciente. Estos campos son opcionales.
                </p>

                <form method="POST" action="{{ route('admin.pacientes.update', $paciente) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('put')

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
