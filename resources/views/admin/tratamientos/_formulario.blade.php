{{--
    Formulario de tratamiento (crear y editar), mismo patrón que "Editar servicio":
    encabezado compacto con "Guardar cambios" arriba en escritorio, todo cabe en
    una PC sin desplazarse y en celular va en una columna con el botón abajo.

    Sin casillas: el estado se cambia desde la lista (interruptor "Activo/Inactivo")
    y "Requiere autorización previa", "Imagen explicativa", "Sesiones requeridas" y
    "Descripción" y "Categoría" se quitaron porque ninguna parte del sistema las
    usaba (los datos se conservan). El precio es obligatorio: se muestra al
    paciente. Sin precio se puede GUARDAR (queda inactivo) pero no PUBLICAR:
    el interruptor de la lista no lo activa hasta que tenga precio.

    La relación con servicios se edita SOLO en el servicio; aquí se muestra
    dónde aparece, junto a la vista previa de cómo lo verá el paciente.

    Variables: $tratamiento (null al crear), $accion (URL), $metodo ('post'|'put').
--}}
@php
    $editando = $tratamiento !== null;
    $valor = fn (string $campo, $omision = null) => old($campo, $editando ? $tratamiento->{$campo} : $omision);
    $servicios = $editando ? $tratamiento->servicios()->orderBy('servicios.orden')->get(['servicios.id', 'servicios.nombre', 'servicios.activo']) : collect();
    $claseCampo = 'mt-1.5 block w-full rounded-xl border border-field bg-white px-3.5 py-2.5 text-sm text-ink shadow-2xs transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200';
@endphp

<div class="pb-8 pt-4">
    <form id="form-tratamiento" method="POST" action="{{ $accion }}"
          data-confirmar="{{ $editando ? 'Los cambios se aplicarán de inmediato.' : 'Se guardará y quedará disponible en el panel.' }}"
          data-confirmar-titulo="{{ $editando ? '¿Guardar los cambios del tratamiento?' : '¿Crear el tratamiento?' }}"
          data-confirmar-boton="{{ $editando ? 'Sí, guardar' : 'Sí, crear' }}" data-confirmar-tono="normal"
          x-data="{
              nombre: @js((string) $valor('nombre', '')),
              duracion: @js((string) $valor('duracion_estimada', '')),
              precio: @js((string) $valor('precio_referencia', '')),
              get duracionTexto() { const m = Number(this.duracion) || 0; if (!m) return 'Duración por definir'; if (m < 60) return m + ' min'; const h = Math.floor(m / 60), r = m % 60; return r ? h + ' h ' + r + ' min' : h + ' h'; },
              get precioTexto() { const p = Number(this.precio); return p > 0 ? new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: p % 1 ? 2 : 0 }).format(p) : 'Falta precio · no se publica'; },
          }"
          class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:px-8">
        @csrf
        @if ($metodo === 'put')
            @method('put')
        @endif

        <section class="min-w-0 rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-datos">
            <h2 id="titulo-datos" class="text-headline font-semibold text-ink">Datos del tratamiento</h2>
            <x-form-obligatorios class="mt-0.5" />

            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <x-input-label :required="true" for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" class="mt-1.5 block w-full" :value="$valor('nombre')" x-model="nombre" required :autofocus="! $editando" />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>


                <div>
                    <x-input-label for="duracion_estimada" value="Duración (minutos)" />
                    <x-text-input id="duracion_estimada" name="duracion_estimada" type="number" min="0" step="5" class="mt-1.5 block w-full" :value="$valor('duracion_estimada')" x-model="duracion" />
                    <p class="mt-1 text-xs text-muted">Define cuánto ocupa en la agenda.</p>
                    <x-input-error :messages="$errors->get('duracion_estimada')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="precio_referencia" value="Precio" />
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm text-muted" aria-hidden="true">$</span>
                        <x-text-input id="precio_referencia" name="precio_referencia" type="number" min="0" step="0.01" class="block w-full pl-7" :value="$valor('precio_referencia')" x-model="precio" />
                    </div>
                    <p class="mt-1 text-xs text-muted">Se muestra al paciente al agendar. Puedes guardar sin precio, pero no se publicará hasta tenerlo.</p>
                    <x-input-error :messages="$errors->get('precio_referencia')" class="mt-2" />
                </div>
            </div>
        </section>

        <aside class="space-y-4 lg:sticky lg:top-28">
            {{-- Así lo verá el paciente en la agenda en línea (se actualiza al escribir). --}}
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft" aria-labelledby="titulo-vista-previa" data-vista-previa-tratamiento>
                <h2 id="titulo-vista-previa" class="text-headline font-semibold text-ink">Así lo verá el paciente</h2>
                {{-- Columna angosta: se muestra como en celular, con el nombre a todo lo ancho. --}}
                <div class="mt-4 flex items-start gap-3 rounded-2xl border-2 border-brand-600 bg-brand-50/50 p-4" aria-hidden="true">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-brand-600 bg-brand-600"><span class="h-1.5 w-1.5 rounded-full bg-white"></span></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-callout font-semibold text-ink [overflow-wrap:anywhere]" x-text="nombre.trim() || 'Nombre del tratamiento'">{{ $valor('nombre') ?: 'Nombre del tratamiento' }}</span>
                        <span class="mt-0.5 block text-footnote">
                            <span class="text-muted" x-text="duracionTexto"></span>
                            <span class="text-muted" aria-hidden="true"> · </span>
                            <span class="font-semibold" :class="Number(precio) > 0 ? 'text-ink' : 'text-rose-700'" x-text="precioTexto"></span>
                        </span>
                    </span>
                </div>
                @if ($servicios->isNotEmpty())
                    <p class="mt-3 text-xs text-muted">
                        Aparece en
                        @foreach ($servicios as $servicio)
                            <a href="{{ route('admin.servicios.edit', $servicio) }}" class="font-semibold text-brand-700 hover:text-brand-800">{{ $servicio->nombre }}</a>@if (! $loop->last), @endif
                        @endforeach
                        · se cambia en el servicio.
                    </p>
                @else
                    <p class="mt-3 text-xs text-rose-700">
                        {{ $editando ? 'Todavía no está en ningún servicio: no se puede agendar.' : 'Después de crearlo, asígnalo a un servicio para que se pueda agendar.' }}
                        <a href="{{ route('admin.servicios.index') }}" class="font-semibold underline">Ir a Servicios</a>
                    </p>
                @endif
            </section>

            {{-- Celular y tableta: guardar abajo, al alcance del pulgar. --}}
            <div class="flex flex-col gap-2.5 rounded-3xl border border-line bg-white p-4 shadow-soft lg:hidden">
                <x-primary-button class="w-full justify-center">{{ $editando ? 'Guardar cambios' : 'Crear tratamiento' }}</x-primary-button>
                <a href="{{ route('admin.tratamientos.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-full text-sm font-semibold text-ink-soft transition hover:text-ink">Cancelar</a>
            </div>
        </aside>
    </form>
</div>
