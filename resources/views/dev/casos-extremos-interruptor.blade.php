{{-- SOLO DESARROLLO: interruptor de datos para la revisión de casos extremos. --}}
@php
    $etiquetas = ['demo' => 'Demo', 'peor' => 'Peor caso', 'vacio' => 'Vacío', 'uno' => 'Uno', 'muchos' => 'Muchos'];
@endphp
<div style="position:fixed;left:50%;bottom:12px;transform:translateX(-50%);z-index:2147483647;display:flex;flex-direction:column;gap:6px;align-items:center;font:500 12px/1 system-ui,sans-serif;max-width:calc(100vw - 16px)">
    <nav aria-label="Pantalla de prueba" style="display:flex;gap:2px;padding:3px;border-radius:999px;background:#e5e5e5;overflow-x:auto;max-width:100%">
        @foreach ($pantallas as $clave => $nombre)
            <a href="{{ url('/dev/casos-extremos/'.$clave) }}?datos={{ $conjuntoActual }}"
               style="padding:7px 10px;border-radius:999px;white-space:nowrap;text-decoration:none;color:#111;{{ $clave === $pantallaActual ? 'background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15)' : '' }}">{{ $nombre }}</a>
        @endforeach
    </nav>
    <nav aria-label="Datos de prueba" style="display:flex;gap:2px;padding:3px;border-radius:999px;background:#e5e5e5;overflow-x:auto;max-width:100%">
        @foreach ($conjuntos as $clave)
            <a href="{{ url('/dev/casos-extremos/'.$pantallaActual) }}?datos={{ $clave }}"
               style="padding:7px 10px;border-radius:999px;white-space:nowrap;text-decoration:none;color:#111;{{ $clave === $conjuntoActual ? 'background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15)' : '' }}">{{ $etiquetas[$clave] }}</a>
        @endforeach
    </nav>
</div>
