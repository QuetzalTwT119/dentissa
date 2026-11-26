{{--
    Descripción para buscadores y vista previa al compartir (26/11/2026). Sin estas
    etiquetas, WhatsApp y Facebook mostraban el enlace sin imagen ni texto, y Google
    tomaba cualquier párrafo de la página.

    Uso: @include('partials.meta-compartir', ['titulo' => '…', 'descripcion' => '…'])
    Opcional: 'imagen' (URL absoluta, 1200x630). La dirección canónica es la página
    actual sin parámetros.
--}}
@php
    $tituloCompartir = $titulo ?? 'Dentissa — Odontología Integral | Dra. Melissa López N.';
    $descripcionCompartir = $descripcion ?? 'Odontología integral y estética con la Dra. Melissa López N. Consultas sin prisas, diagnóstico explicado paso a paso y citas en línea con recordatorio por WhatsApp.';
    $imagenCompartir = $imagen ?? asset('images/dentissa/vista-previa.jpg');
    $urlCompartir = url()->current();
@endphp
<meta name="description" content="{{ $descripcionCompartir }}">
<link rel="canonical" href="{{ $urlCompartir }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="es_MX">
<meta property="og:site_name" content="Dentissa · Dra. Melissa López">
<meta property="og:title" content="{{ $tituloCompartir }}">
<meta property="og:description" content="{{ $descripcionCompartir }}">
<meta property="og:url" content="{{ $urlCompartir }}">
<meta property="og:image" content="{{ $imagenCompartir }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Dra. Melissa López, odontología integral">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $tituloCompartir }}">
<meta name="twitter:description" content="{{ $descripcionCompartir }}">
<meta name="twitter:image" content="{{ $imagenCompartir }}">
