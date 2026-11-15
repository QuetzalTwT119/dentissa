<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="mt-1.5 text-[1.7rem] leading-tight text-ink sm:text-3xl">
                    Editar consulta — {{ $paciente->nombre }} {{ $paciente->apellido }}
                </h2>
                <p class="mt-1 max-w-2xl text-sm text-muted">Modificación de notas clínicas y gestión de adjuntos.</p>
            </div>
            <a href="{{ route('admin.pacientes.historial.index', $paciente) }}" class="hidden sm:inline-block">
                <x-secondary-button type="button">Volver al historial</x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <form method="POST" action="{{ route('admin.pacientes.historial.update', [$paciente, $historial]) }}" class="space-y-6">
                    @csrf
                    @method('put')

                    @include('admin.historial._campos')

                    <div class="flex items-center gap-4 pt-4 border-t border-line">
                        <x-primary-button>Guardar cambios</x-primary-button>

                        <a href="{{ route('admin.pacientes.historial.index', $paciente) }}">
                            <x-secondary-button type="button">Volver</x-secondary-button>
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-8">
                <h3 class="text-lg font-semibold text-ink">Documentos y radiografías</h3>
                <p class="mt-1 text-xs text-muted">Archivos clínicos complementarios vinculados a esta sesión.</p>

                <ul class="mt-4 space-y-2.5">
                    @forelse ($historial->imagenes as $imagen)
                        <li class="flex items-center justify-between gap-3 rounded-xl border border-line bg-cream/60 px-4 py-2.5 text-sm transition hover:bg-white hover:border-peach-300">
                            <a href="{{ route('historial.imagenes.show', $imagen) }}" target="_blank" rel="noreferrer" class="min-w-0 break-words font-medium text-brand-600 hover:text-brand-800">
                                {{ $imagen->titulo ?? $imagen->url }}
                            </a>
                            <form method="POST" action="{{ route('admin.pacientes.historial.imagenes.destroy', [$paciente, $historial, $imagen]) }}"
                                  onsubmit="return confirm('¿Eliminar este adjunto?');">
                                @csrf
                                @method('delete')
                                <button type="submit" class="shrink-0 text-xs font-semibold text-rose-600 transition hover:text-rose-800">Eliminar</button>
                            </form>
                        </li>
                    @empty
                        <li class="rounded-xl border border-dashed border-line p-4 text-center text-sm text-muted">Todavía no hay adjuntos para esta consulta.</li>
                    @endforelse
                </ul>

                <form method="POST" action="{{ route('admin.pacientes.historial.imagenes.store', [$paciente, $historial]) }}" enctype="multipart/form-data" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-4 border-t border-line pt-5">
                    @csrf
                    <div class="min-w-0 flex-1">
                        <x-input-label for="archivo" value="Nuevo adjunto (imagen o PDF, máx. 5MB)" />
                        <input id="archivo" name="archivo" type="file" accept="image/*,.pdf" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                        <x-input-error :messages="$errors->get('archivo')" class="mt-2" />
                    </div>
                    <x-primary-button>Subir</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
