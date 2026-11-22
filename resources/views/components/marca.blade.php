{{--
    Marca de Dentissa (22/11/2026): el logotipo con la firma de la portada en
    TODO el sistema. Antes había cuatro versiones (portada "Dra. Melissa
    López", agenda pública "… N. & Armonización", inicio de sesión
    "Melissa López N." y panel "Dentissa / Administración").

    <x-marca :href="url('/')" />
    <x-marca :href="route('dashboard')" texto-desde="xl" />   (panel: entre 1024 y 1280 px solo el círculo)
    <x-marca tono="oscuro" tamano="grande" />                  (sobre foto oscura)

    Props:
      - href: a dónde lleva (sin href, no es enlace).
      - tono: claro (por omisión) | oscuro (texto blanco sobre fondo oscuro).
      - tamano: normal | grande.
      - textoDesde: '' (siempre) | 'sm' (desde 640 px) | 'xl' (todo menos 1024–1280 px):
        donde no se ve queda solo el círculo (el nombre sigue para lectores de pantalla).
--}}
@props(['href' => null, 'tono' => 'claro', 'tamano' => 'normal', 'textoDesde' => ''])

@php
    $oscuro = $tono === 'oscuro';
    $grande = $tamano === 'grande';
    // Clases literales para que Tailwind las incluya.
    // 'xl' (panel): la firma se esconde SOLO entre 1024 y 1280 px, donde el menú
    // completo necesita el espacio. En celular y tableta (hamburguesa) sí cabe.
    $textoVisible = ['' => '', 'sm' => 'sr-only sm:not-sr-only', 'xl' => 'lg:sr-only xl:not-sr-only'][$textoDesde] ?? '';
    $etiqueta = $href ? 'a' : 'span';
@endphp

<{{ $etiqueta }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'flex min-w-0 shrink-0 items-center gap-3 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-300']) }}
    data-marca>
    <span @class([
        'flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white p-0.5',
        'h-11 w-11 ring-1 ring-peach-200' => ! $grande,
        'h-12 w-12 shadow-lg ring-1 ring-white/50 sm:h-14 sm:w-14' => $grande,
    ])>
        <img src="{{ asset('images/9.jpg') }}" alt="" class="h-full w-full rounded-full object-contain" width="56" height="56">
    </span>
    <span @class(['min-w-0 leading-none', $textoVisible])>
        <span @class([
            'block truncate firma leading-none',
            'text-[1.2rem] sm:text-[1.35rem]' => ! $grande,
            'text-xl sm:text-[1.4rem]' => $grande,
            'text-brand-700' => ! $oscuro,
            'text-white drop-shadow-soft' => $oscuro,
        ])>Dra. Melissa López</span>
        <span @class([
            'mt-1 block text-caption font-semibold uppercase tracking-label',
            'text-muted' => ! $oscuro,
            'text-white/90' => $oscuro,
        ])>Odontología Integral</span>
    </span>
</{{ $etiqueta }}>
