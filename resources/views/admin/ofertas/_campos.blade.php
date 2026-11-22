@php
$oferta ??= null;
$valor = function (string $campo, $default = '') use ($oferta) {
    $actual = $oferta?->{$campo} ?? $default;
    if ($actual instanceof \BackedEnum) {
        $actual = $actual->value;
    }
    // Los <input type="date"> solo aceptan AAAA-MM-DD (antes Editar los dejaba vacíos).
    if ($actual instanceof \DateTimeInterface) {
        $actual = $actual->format('Y-m-d');
    }
    return old($campo, $actual);
};

// "Beneficio para el paciente" (21/11/2026): Descuento (en % o $), Precio
// especial o Gratis + una cantidad, en lugar de Tipo de oferta, Tipo de valor,
// Valor del descuento y Precio especial. Antes "% de descuento" y "$ de
// descuento" eran dos botones para lo mismo: ahora la unidad va junto a la cantidad.
// Al crear no hay ninguno elegido: el administrador lo elige a propósito.
$interno = $oferta?->beneficio();
$beneficio = (string) old('beneficio', in_array($interno, ['porcentaje', 'monto'], true) ? 'descuento' : ($interno ?? ''));
$unidad = (string) old('unidad', $interno === 'monto' ? 'monto' : 'porcentaje');
$cantidad = old('cantidad', $oferta?->cantidadBeneficio());
$cantidad = $cantidad === null ? '' : rtrim(rtrim(number_format((float) $cantidad, 2, '.', ''), '0'), '.');

// "Más detalles": lo opcional va plegado. Se abre solo si ya tiene algo
// escrito ahí o si alguno de esos campos tiene un error.
$abrirDetalles = $errors->hasAny(['cupo_total', 'imagen'])
    || (int) old('cupo_total', $oferta?->cupo_total) > 0
    || filled($oferta?->imagen);
@endphp

<div>
    <x-input-label :required="true" for="titulo" value="Título" />
    <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="$valor('titulo')" required minlength="5" maxlength="120" autofocus placeholder="Ej. Limpieza de fin de año" />
    <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
</div>

{{-- En PC esta fila entra a la cuadrícula (display: contents): Beneficio | Cantidad. --}}
<div x-data="{ beneficio: @js($beneficio), unidad: @js($unidad) }" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    {{-- Opción única con el diseño global x-opcion; tocar la elegida la quita. --}}
    <fieldset>
        <legend class="text-sm font-semibold text-ink">Beneficio para el paciente <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span></legend>
        {{-- Ancho natural: "Precio especial" no se parte en dos líneas. --}}
        <div class="mt-1.5 flex flex-wrap gap-2">
            @foreach (\App\Models\Oferta::BENEFICIOS as $clave => $texto)
                <x-opcion name="beneficio" :value="$clave" modelo="beneficio" :desmarcable="true" :requerido="true" :checked="$beneficio === $clave" class="whitespace-nowrap px-4">{{ $texto }}</x-opcion>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('beneficio')" class="mt-2" />
    </fieldset>

    <div>
        <template x-if="beneficio === 'descuento' || beneficio === 'precio'">
            <div>
                <label for="cantidad" class="block text-sm font-semibold text-ink">
                    <span x-text="beneficio === 'precio' ? 'Precio con la oferta' : 'Porcentaje de descuento'">Cantidad</span>
                    <span class="text-rose-700" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                </label>
                {{--
                    Descuento = siempre en % (21/11/2026): no hay botón de unidad. Un
                    monto fijo en pesos se escribe como "Precio especial". Las ofertas
                    viejas en $ conservan su unidad al editarlas (campo oculto).
                --}}
                <input type="hidden" name="unidad" value="{{ $unidad }}">
                <div class="relative mt-1.5">
                    <span x-show="beneficio === 'precio' || unidad === 'monto'" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-muted" aria-hidden="true">$</span>
                    <x-text-input id="cantidad" name="cantidad" type="number" step="0.01" min="1"
                                  x-bind:max="beneficio === 'descuento' && unidad === 'porcentaje' ? 99 : 999999"
                                  class="block w-full" x-bind:class="beneficio === 'precio' || unidad === 'monto' ? 'pl-7' : 'pr-9'" :value="$cantidad" required />
                    <span x-show="beneficio === 'descuento' && unidad === 'porcentaje'" class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-sm text-muted" aria-hidden="true">%</span>
                </div>
            </div>
        </template>
        <p x-show="beneficio === 'gratis'" x-cloak class="flex min-h-11 items-center text-sm text-muted sm:pt-7">Sin cantidad: en la portada dirá «Gratis».</p>
        <p x-show="! beneficio" @if ($beneficio !== '') x-cloak @endif class="flex min-h-11 items-center text-sm text-muted sm:pt-7">Elige el beneficio para escribir la cantidad.</p>
        <x-input-error :messages="$errors->get('cantidad')" class="mt-2" />
        <x-input-error :messages="$errors->get('unidad')" class="mt-2" />
    </div>
</div>

<div x-data="{ largo: @js(mb_strlen((string) $valor('descripcion'))) }">
    <x-input-label :required="true" for="descripcion" value="Descripción" />
    <x-textarea id="descripcion" name="descripcion" rows="3" class="mt-1.5" required minlength="20" maxlength="300"
                x-on:input="largo = $event.target.value.length" aria-describedby="descripcion-ayuda"
                placeholder="Qué incluye y para quién es. Sin enlaces ni correos.">{{ $valor('descripcion') }}</x-textarea>
    <p id="descripcion-ayuda" class="mt-1 text-xs text-muted">
        <span x-text="largo">{{ mb_strlen((string) $valor('descripcion')) }}</span>/300 · mínimo 20 caracteres
    </p>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>

<div>
    <x-input-label :required="true" for="fecha_fin" value="Válida hasta" />
    <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1.5 block w-full" :value="$valor('fecha_fin')" min="{{ now()->toDateString() }}" required />
    <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
</div>

{{--
    Tratamiento a la vista (22/11/2026; antes en "Más detalles"): desde que las
    ofertas se ligan a la cita, decide si la oferta se aplica al agendar.
--}}
<div>
    <x-input-label for="id_tratamiento" value="Tratamiento al que aplica" />
    <x-select id="id_tratamiento" name="id_tratamiento" class="mt-1.5" aria-describedby="id_tratamiento-ayuda">
        <option value="">Ninguno en particular</option>
        @foreach ($tratamientos as $tratamiento)
            <option value="{{ $tratamiento->id }}" @selected($valor('id_tratamiento') == $tratamiento->id)>{{ $tratamiento->nombre }}</option>
        @endforeach
    </x-select>
    <p id="id_tratamiento-ayuda" class="mt-1 text-xs text-muted">Sin tratamiento, la oferta solo se anuncia: no se aplica al agendar.</p>
    <x-input-error :messages="$errors->get('id_tratamiento')" class="mt-2" />
</div>

<details class="col-completa group/detalles rounded-2xl border border-line bg-cream/60" @if ($abrirDetalles) open @endif data-mas-detalles>
    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-sm font-semibold text-ink hover:text-brand-800 [&::-webkit-details-marker]:hidden">
        <svg class="h-4 w-4 shrink-0 transition-transform group-open/detalles:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.2 4.2a1 1 0 0 1 1.4 0l5 5a1 1 0 0 1 0 1.4l-5 5a1 1 0 0 1-1.4-1.4L11.5 10 7.2 5.6a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
        Más detalles (opcional)
        <span class="font-normal text-muted">· lugares e imagen</span>
    </summary>

    <div class="grid grid-cols-1 gap-5 border-t border-line px-4 pb-4 pt-4 lg:grid-cols-3">
        <div>
            <x-input-label for="cupo_total" value="Lugares disponibles" />
            <x-text-input id="cupo_total" name="cupo_total" type="number" min="0" max="9999" class="mt-1.5 block w-full" :value="$valor('cupo_total') ?: ''" placeholder="Vacío = sin límite" />
            <x-input-error :messages="$errors->get('cupo_total')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="imagen" value="Imagen (se ve en la portada)" />
            @if ($oferta?->imagen)
                <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="mb-2 mt-2 h-16 w-16 rounded-xl border border-line object-cover shadow-2xs">
            @endif
            <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-ink file:mr-3 file:rounded-xl file:border-0 file:bg-cream file:px-4 file:py-2 file:text-xs file:font-semibold file:text-ink hover:file:bg-shell">
            <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
        </div>
    </div>
</details>
