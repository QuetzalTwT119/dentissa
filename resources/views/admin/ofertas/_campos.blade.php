@php
$oferta ??= null;
$valor = function (string $campo, $default = '') use ($oferta) {
    $actual = $oferta?->{$campo} ?? $default;
    if ($actual instanceof \BackedEnum) {
        $actual = $actual->value;
    }
    return old($campo, $actual);
};
@endphp

<div>
    <x-input-label for="titulo" value="Título" />
    <x-text-input id="titulo" name="titulo" class="mt-1.5 block w-full" :value="$valor('titulo')" required autofocus />
    <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
</div>

<div>
    <x-input-label for="descripcion" value="Descripción" />
    <textarea id="descripcion" name="descripcion" rows="3"
              class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition placeholder:text-[#8A7A7D]/70 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">{{ $valor('descripcion') }}</textarea>
    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
</div>

<div>
    <x-input-label for="id_tratamiento" value="Tratamiento relacionado (opcional)" />
    <select id="id_tratamiento" name="id_tratamiento"
            class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
        <option value="">Ninguno</option>
        @foreach ($tratamientos as $tratamiento)
            <option value="{{ $tratamiento->id }}" @selected($valor('id_tratamiento') == $tratamiento->id)>{{ $tratamiento->nombre }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('id_tratamiento')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="tipo" value="Tipo de oferta" />
        <select id="tipo" name="tipo"
                class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
            @foreach (['descuento' => 'Descuento', 'paquete' => 'Paquete', 'bonificacion' => 'Bonificación'] as $valorTipo => $etiqueta)
                <option value="{{ $valorTipo }}" @selected($valor('tipo', 'descuento') === $valorTipo)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="tipo_valor" value="Tipo de valor" />
        <select id="tipo_valor" name="tipo_valor"
                class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
            <option value="porcentaje" @selected($valor('tipo_valor', 'porcentaje') === 'porcentaje')>Porcentaje</option>
            <option value="monto_fijo" @selected($valor('tipo_valor', 'porcentaje') === 'monto_fijo')>Monto fijo</option>
        </select>
        <x-input-error :messages="$errors->get('tipo_valor')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="valor_descuento" value="Valor del descuento" />
        <x-text-input id="valor_descuento" name="valor_descuento" type="number" step="0.01" class="mt-1.5 block w-full" :value="$valor('valor_descuento')" />
        <x-input-error :messages="$errors->get('valor_descuento')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="precio_especial" value="Precio especial" />
        <x-text-input id="precio_especial" name="precio_especial" type="number" step="0.01" class="mt-1.5 block w-full" :value="$valor('precio_especial')" />
        <x-input-error :messages="$errors->get('precio_especial')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
    <div>
        <x-input-label for="fecha_inicio" value="Fecha de inicio" />
        <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1.5 block w-full" :value="$valor('fecha_inicio')" required />
        <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="fecha_fin" value="Fecha de fin" />
        <x-text-input id="fecha_fin" name="fecha_fin" type="date" class="mt-1.5 block w-full" :value="$valor('fecha_fin')" required />
        <x-input-error :messages="$errors->get('fecha_fin')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="cupo_total" value="Cupo total (0 = sin límite)" />
    <x-text-input id="cupo_total" name="cupo_total" type="number" min="0" class="mt-1.5 block w-full" :value="$valor('cupo_total')" />
    <x-input-error :messages="$errors->get('cupo_total')" class="mt-2" />
</div>

<div>
    <x-input-label for="imagen" value="Imagen de la promoción" />
    @if ($oferta?->imagen)
        <div class="mt-2 mb-3">
            <img src="/storage/{{ $oferta->imagen }}" alt="{{ $oferta->titulo }}" class="h-20 w-20 rounded-xl border border-[#e9dfdb] object-cover shadow-sm">
        </div>
    @endif
    <input id="imagen" name="imagen" type="file" accept="image/*" class="mt-1.5 block w-full text-sm text-[#2D2526] file:mr-3 file:rounded-xl file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
    <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
</div>

<div class="flex items-center pt-2">
    <x-checkbox id="activa" name="activa" value="1" :checked="$valor('activa', true)" />
    <x-input-label for="activa" value="Oferta activa" class="ml-2.5" />
</div>
