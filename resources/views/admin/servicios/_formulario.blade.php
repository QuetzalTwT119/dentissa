{{--
    Formulario ÚNICO de servicio (Nuevo y Editar, 22/11/2026). Antes "Nuevo
    servicio" era el formulario viejo (campo "Icono" que no se usaba, casilla
    "Servicio activo" y sin tratamientos ni vista previa) y "Editar" el nuevo:
    dos pantallas distintas para lo mismo.

    Un solo formulario (datos, foto y tratamientos) con un solo botón. Antes
    había dos formularios y elegir una foto para luego pulsar "Guardar
    tratamientos" la perdía sin avisar.

    Composición de escritorio pensada para caber en una pantalla de PC sin
    desplazarse: encabezado compacto (migas + nombre + botones), formulario
    ancho a la izquierda y, fija a la derecha, la vista previa de la tarjeta tal
    como sale en el carrusel. En celular todo va en una columna y el botón de
    guardar queda abajo, al alcance del pulgar.

    Variables: $servicio (nuevo o existente), $tratamientos, $tratamientosAsignados.
--}}
@php
    $esNuevo = ! $servicio->exists;
    $descripcionPredeterminada = config('dentissa.descripciones_servicios')[$servicio->nombre] ?? null;
    $asignadosActuales = collect(old('tratamiento_ids', $tratamientosAsignados->all()))->map(fn ($id) => (int) $id);
    $textoGuardar = $esNuevo ? 'Crear servicio' : 'Guardar cambios';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <x-volver :href="route('admin.servicios.index')" texto="Volver a Servicios" />
                <h1 class="mt-1 truncate text-title-2 text-ink">{{ $esNuevo ? 'Nuevo servicio' : $servicio->nombre }}</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.servicios.index') }}" class="hidden sm:inline-block">
                    <x-secondary-button type="button">Cancelar</x-secondary-button>
                </a>
                {{-- En escritorio el botón vive arriba: siempre a la vista y sin desplazarse. --}}
                <x-primary-button form="form-servicio" class="hidden lg:inline-flex" data-guardar-arriba>{{ $textoGuardar }}</x-primary-button>
            </div>
        </div>
    </x-slot>

    <div class="panel-contenido pb-8 pt-4 lg:pb-3 lg:pt-3">
        <form id="form-servicio" method="POST" action="{{ $esNuevo ? route('admin.servicios.store') : route('admin.servicios.update', $servicio) }}"
              @if ($esNuevo)
                  data-confirmar="Se agregará al final del carrusel, se mostrará en la portada y se podrá agendar. Para ocultarlo, usa el interruptor de la lista."
                  data-confirmar-titulo="¿Crear el servicio?" data-confirmar-boton="Sí, crear"
              @else
                  data-confirmar="Los cambios se aplicarán de inmediato."
                  data-confirmar-titulo="¿Guardar los cambios del servicio?" data-confirmar-boton="Sí, guardar"
              @endif
              data-confirmar-tono="normal" enctype="multipart/form-data"
              x-data="{ vistaPrevia: null, nombreArchivo: '', seleccionados: {{ $asignadosActuales->count() }}, nombre: @js((string) old('nombre', $servicio->nombre)), descripcion: @js((string) old('descripcion', $servicio->descripcion)), predeterminada: @js((string) $descripcionPredeterminada) }"
              class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:px-8">
            @csrf
            @unless ($esNuevo)
                @method('put')
            @endunless
            <input type="hidden" name="tratamientos_enviados" value="1">

            <div class="min-w-0 space-y-6">
                {{-- Datos --}}
                <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-datos">
                    <h2 id="titulo-datos" class="text-headline font-semibold text-ink">Datos del servicio</h2>
                    <x-form-obligatorios class="mt-0.5" />
                    {{--
                        Sin interruptor "Disponible" (22/11/2026, decisión del usuario): se cambia
                        SOLO desde la lista de Servicios, con confirmación. Tenerlo también aquí eran
                        dos lugares para lo mismo. Un servicio nuevo nace disponible. Si se edita uno
                        oculto, se avisa (sin control) para que no sorprenda que no salga en la portada.
                    --}}
                    @if (! $esNuevo && ! $servicio->activo)
                        <p class="mt-3 rounded-xl border border-amber-200/70 bg-amber-50/60 px-3.5 py-2.5 text-sm text-amber-900" data-aviso-no-disponible>
                            Este servicio <strong>no está disponible</strong>: no se muestra en la portada ni se puede agendar. Actívalo en la lista de <a href="{{ route('admin.servicios.index') }}" class="font-semibold underline underline-offset-2">Servicios</a>.
                        </p>
                    @endif

                    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                        <div>
                            <x-input-label :required="true" for="nombre" value="Nombre" />
                            <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="old('nombre', $servicio->nombre)" x-model="nombre" required :autofocus="$esNuevo" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                        </div>

                        {{--
                            Sin texto de ejemplo ni leyendas: si se deja vacío, la tarjeta de la
                            derecha (y la portada) usa la descripción predeterminada de la clínica.
                        --}}
                        <div>
                            <x-input-label for="descripcion" value="Descripción en la portada" />
                            <x-textarea id="descripcion" name="descripcion" rows="2" x-model="descripcion" class="mt-1.5">{{ old('descripcion', $servicio->descripcion) }}</x-textarea>
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
                                <span class="min-w-0 text-sm font-medium text-ink">
                                    {{ $tratamiento->nombre }}
                                    {{-- Puede estar en varios servicios: se avisa sin saturar (un nombre + contador). --}}
                                    @if ($tratamiento->servicios->isNotEmpty())
                                        <span class="block text-xs font-normal text-muted" data-tambien-en title="También en: {{ $tratamiento->servicios->pluck('nombre')->implode(', ') }}">
                                            También en {{ $tratamiento->servicios->first()->nombre }}@if ($tratamiento->servicios->count() > 1) +{{ $tratamiento->servicios->count() - 1 }}@endif
                                        </span>
                                    @endif
                                </span>
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
                        <img src="{{ $servicio->fotoPublica() }}" x-bind:src="vistaPrevia ?? @js($servicio->fotoPublica())" alt="Vista previa de la tarjeta del servicio" data-vista-previa-servicio
                             class="absolute inset-0 -z-20 h-full w-full object-cover">
                        <span aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-[#2b1a20]/90 via-[#2b1a20]/35 to-[#2b1a20]/5"></span>
                        <p class="font-serif text-title-3 font-medium leading-tight" x-text="nombre.trim() || 'Nombre del servicio'">{{ $servicio->nombre ?: 'Nombre del servicio' }}</p>
                        <p class="mt-1.5 line-clamp-3 text-sm text-white/85" data-vista-previa-descripcion
                           x-text="descripcion.trim() || predeterminada" x-show="descripcion.trim() || predeterminada">{{ $servicio->descripcionPublica() }}</p>
                    </div>

                    <p class="mt-3 text-xs font-semibold text-brand-700" x-show="vistaPrevia" x-cloak>
                        Nueva foto: <span x-text="nombreArchivo"></span>. Se guarda al pulsar "{{ $textoGuardar }}".
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
                <x-admin.card class="flex flex-col gap-2.5 p-4 lg:hidden">
                    <x-primary-button class="w-full justify-center">{{ $textoGuardar }}</x-primary-button>
                    <a href="{{ route('admin.servicios.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold text-ink-soft transition hover:text-ink">Cancelar</a>
                </x-admin.card>
            </aside>
        </form>
    </div>
</x-app-layout>
