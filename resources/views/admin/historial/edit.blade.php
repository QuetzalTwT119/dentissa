<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Editar consulta — {{ $paciente->nombre }} {{ $paciente->apellido }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-2xl space-y-6 sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.pacientes.historial.update', [$paciente, $historial]) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('admin.historial._campos')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900">Documentos y radiografías</h3>

                <ul class="mt-4 space-y-2">
                    @forelse ($historial->imagenes as $imagen)
                        <li class="flex items-center justify-between gap-3 rounded-md border border-gray-200 px-3 py-2 text-sm">
                            <a href="{{ route('historial.imagenes.show', $imagen) }}" target="_blank" rel="noreferrer" class="min-w-0 break-words text-brand-600 hover:text-brand-900">
                                {{ $imagen->titulo ?? $imagen->url }}
                            </a>
                            <form method="POST" action="{{ route('admin.pacientes.historial.imagenes.destroy', [$paciente, $historial, $imagen]) }}"
                                  onsubmit="return confirm('¿Eliminar este adjunto?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="shrink-0 text-red-600 hover:text-red-900">Eliminar</button>
                            </form>
                        </li>
                    @empty
                        <p class="text-sm text-gray-500">Todavía no hay adjuntos.</p>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('admin.pacientes.historial.imagenes.store', [$paciente, $historial]) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <x-input-label for="archivo" value="Nuevo adjunto (imagen o PDF, máx. 5MB)" />
                        <input id="archivo" name="archivo" type="file" accept="image/*,.pdf" class="mt-1 block w-full text-sm">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>
                    <x-primary-button>Subir</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
