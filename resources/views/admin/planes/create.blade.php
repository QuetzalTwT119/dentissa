{{-- Nuevo plan de tratamiento (24/11/2026): título y notas; los pasos se agregan después. --}}
<x-app-layout>
    <x-slot name="header">
        <x-admin.header :volver="route('admin.pacientes.historial.index', $paciente)" volver-texto="Volver al expediente"
                        title="Nuevo plan de tratamiento — {{ $paciente->nombre_completo }}" subtitle="Ponle un nombre; después agregas sus pasos." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-admin.card class="p-5 sm:p-8">
                <form method="POST" action="{{ route('admin.pacientes.planes.store', $paciente) }}" class="formulario-panel formulario-una-columna space-y-6"
                      data-confirmar="Después podrás agregar sus pasos." data-confirmar-titulo="¿Crear el plan?" data-confirmar-boton="Sí, crear" data-confirmar-tono="normal" data-nuevo-plan>
                    @csrf
                    <x-form-obligatorios />
                    <div>
                        <x-input-label :required="true" for="titulo" value="Nombre del plan" />
                        <x-text-input id="titulo" name="titulo" maxlength="120" class="mt-1.5 block w-full" required autofocus
                                      :value="old('titulo')" placeholder="Ortodoncia, rehabilitación, limpieza y resinas…" />
                        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="notas" value="Notas (opcional)" />
                        <x-textarea id="notas" name="notas" rows="3" class="mt-1.5">{{ old('notas') }}</x-textarea>
                        <x-input-error :messages="$errors->get('notas')" class="mt-2" />
                    </div>
                    <div class="flex items-center gap-4 border-t border-line pt-4">
                        <x-primary-button>Crear plan</x-primary-button>
                        <x-boton-cancelar href="{{ route('admin.pacientes.historial.index', $paciente) }}" />
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
