{{--
    Editar servicio: un solo formulario (datos, foto y tratamientos) con un solo
    botón. Antes había dos formularios y elegir una foto para luego pulsar
    "Guardar tratamientos" la perdía sin avisar.

    Composición de escritorio pensada para caber en una pantalla de PC sin
    desplazarse, sin apretar: encabezado compacto (migas + nombre + botones),
    formulario ancho a la izquierda y, fija a la derecha, la vista previa de la
    tarjeta tal como sale en el carrusel. En celular todo va en una columna y
    "Guardar cambios" queda abajo, al alcance del pulgar.
--}}
@php
    $descripcionPredeterminada = config('dentissa.descripciones_servicios')[$servicio->nombre] ?? null;
    $asignadosActuales = collect(old('tratamiento_ids', $tratamientosAsignados->all()))->map(fn ($id) => (int) $id);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <nav aria-label="Ruta" class="text-sm text-muted">
                    <a href="{{ route('admin.servicios.index') }}" class="font-semibold text-brand-700 hover:text-brand-800">Servicios</a>
                    <span aria-hidden="true" class="mx-1.5">›</span>
                    <span>Editar</span>
                </nav>
                <h1 class="mt-1 truncate text-title-2 text-ink">{{ $servicio->nombre }}</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.servicios.index') }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Volver al listado</x-secondary-button>
                </a>
                {{-- En escritorio el botón vive arriba: siempre a la vista y sin desplazarse. --}}
                <x-primary-button form="form-servicio" class="hidden lg:inline-flex" data-guardar-arriba>Guardar cambios</x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="pb-8 pt-4">
        <form id="form-servicio" method="POST" action="{{ route('admin.servicios.update', $servicio) }}" enctype="multipart/form-data"
              x-data="{ vistaPrevia: null, nombreArchivo: '', seleccionados: {{ $asignadosActuales->count() }}, nombre: @js(old('nombre', $servicio->nombre)), descripcion: @js((string) old('descripcion', $servicio->descripcion)), predeterminada: @js((string) $descripcionPredeterminada) }"
              class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:px-8">
            @csrf
            @method('put')
            <input type="hidden" name="tratamientos_enviados" value="1">

            <div class="min-w-0 space-y-6">
                {{-- Datos --}}
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-datos">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="titulo-datos" class="text-headline font-semibold text-ink">Datos del servicio</h2>
                            <x-form-obligatorios class="mt-0.5" />
                        </div>

                        {{-- Interruptor: ocupa la esquina en lugar de un renglón entero. --}}
                        <label for="activo" class="inline-flex min-h-11 cursor-pointer items-center gap-3 rounded-full border border-line bg-cream/40 py-1.5 pl-4 pr-2">
                            <span class="text-sm font-medium text-ink">Visible en la portada</span>
                            <input type="hidden" name="activo" value="0">
                            <input id="activo" name="activo" type="checkbox" value="1" role="switch" class="peer sr-only" @checked(old('activo', $servicio->activo))>
                            <span aria-hidden="true" class="relative h-6 w-11 rounded-full bg-line transition-colors duration-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:duration-200 after:ease-out peer-checked:bg-sage-600 peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-300 motion-reduce:after:transition-none"></span>
                        </label>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                        <div>
                            <x-input-label :required="true" for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre', $servicio->nombre)" x-model="nombre" required />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        {{--
                            Sin texto de ejemplo ni leyendas: si se deja vacío, la tarjeta de la
                            derecha (y la portada) usa la descripción predeterminada de la clínica.
                        --}}
                        <div>
                            <x-input-label for="descripcion" value="Descripción en la portada" />
                            <textarea id="descripcion" name="descripcion" rows="2" x-model="descripcion"
                                      class="mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ old('descripcion', $servicio->descripcion) }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- Tratamientos --}}
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-tratamientos">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                        <h2 id="titulo-tratamientos" class="text-headline font-semibold text-ink">Tratamientos que se pueden agendar</h2>
                        <p class="text-sm text-muted" aria-live="polite"><span x-text="seleccionados">{{ $asignadosActuales->count() }}</span> seleccionados</p>
                    </div>
                    <p class="mt-1 text-sm text-muted">Solo aparecen los activos. Sin ninguno, la portada lo muestra como "Agenda por teléfono".</p>

                    <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse ($tratamientos as $tratamiento)
                            <label for="tratamiento-{{ $tratamiento->id }}" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-line bg-cream/40 px-3 py-2.5 transition hover:border-peach-300 hover:bg-white has-[:checked]:border-brand-200 has-[:checked]:bg-brand-50/60">
                                <x-checkbox id="tratamiento-{{ $tratamiento->id }}" name="tratamiento_ids[]" value="{{ $tratamiento->id }}"
                                            :checked="$asignadosActuales->contains($tratamiento->id)"
                                            x-on:change="seleccionados += $event.target.checked ? 1 : -1" />
                                <span class="min-w-0 text-sm font-medium text-ink">{{ $tratamiento->nombre }}</span>
                            </label>
                        @empty
                            <p class="col-span-full text-sm text-muted">Todavía no hay tratamientos activos. Créalos en Catálogo → Tratamientos.</p>
                        @endforelse
                    </div>
                    <x-input-error :messages="$errors->get('tratamiento_ids')" class="mt-2" />
                </section>
            </div>

            {{-- Foto: fija a la derecha en escritorio --}}
            <aside class="space-y-4 lg:sticky lg:top-28">
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft" aria-labelledby="titulo-foto">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="titulo-foto" class="text-headline font-semibold text-ink">Foto en la portada</h2>
                        <span class="text-xs text-muted" x-show="!vistaPrevia">{{ $servicio->imagen ? 'Foto subida por la clínica.' : 'Foto predeterminada de la clínica.' }}</span>
                    </div>

                    {{-- Misma tarjeta que el carrusel, para ver el resultado antes de guardar. --}}
                    <div class="relative isolate mt-4 flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-[1.5rem] p-5 text-white">
                        <img src="{{ $servicio->fotoPublica() }}" x-bind:src="vistaPrevia ?? @js($servicio->fotoPublica())" alt="Vista previa de la tarjeta de {{ $servicio->nombre }}" data-vista-previa-servicio
                             class="absolute inset-0 -z-20 h-full w-full object-cover">
                        <span aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-[#2b1a20]/90 via-[#2b1a20]/35 to-[#2b1a20]/5"></span>
                        <p class="font-serif text-title-3 font-medium leading-tight" x-text="nombre">{{ $servicio->nombre }}</p>
                        <p class="mt-1.5 line-clamp-3 text-sm text-white/85" data-vista-previa-descripcion
                           x-text="descripcion.trim() || predeterminada" x-show="descripcion.trim() || predeterminada">{{ $servicio->descripcionPublica() }}</p>
                    </div>

                    <p class="mt-3 text-xs font-semibold text-brand-700" x-show="vistaPrevia" x-cloak>
                        Nueva foto: <span x-text="nombreArchivo"></span>. Se guarda al pulsar "Guardar cambios".
                    </p>

                    <label for="imagen" title="JPG, PNG o WebP, hasta 5 MB. Se ve mejor vertical." class="mt-3 flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-full border border-line bg-white px-4 text-sm font-semibold text-ink transition hover:border-peach-300 focus-within:ring-2 focus-within:ring-brand-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $servicio->imagen ? 'Cambiar foto' : 'Subir foto' }} <span class="font-normal text-muted">· hasta 5 MB</span>
                        <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only"
                               x-on:change="const f = $event.target.files[0]; vistaPrevia = f ? URL.createObjectURL(f) : null; nombreArchivo = f ? f.name : ''">
                    </label>
                    <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                </section>

                {{-- Celular y tableta: guardar abajo, al alcance del pulgar. --}}
                <div class="flex flex-col gap-2.5 rounded-3xl border border-line bg-white p-4 shadow-soft lg:hidden">
                    <x-primary-button class="w-full justify-center">Guardar cambios</x-primary-button>
                    <a href="{{ route('admin.servicios.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold text-ink-soft transition hover:text-ink">Cancelar</a>
                </div>
            </aside>
        </form>
    </div>
</x-app-layout>
