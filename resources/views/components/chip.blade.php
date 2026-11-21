{{--
    Etiqueta redonda global ("chip"), 21/11/2026: rol, estado, categoría, tipo.
    La forma es siempre la misma; el color se pasa por class y tiene significado:
      verde (sage) = activo/aprobado · rosa (brand/peach) = marca/destacado ·
      crema (cream + border-line) = neutro · ámbar = pendiente · rosa fuerte (rose) = rechazado.
    Para Activo/Inactivo usa <x-admin.status-badge> (lleva punto de color).
    Uso: <x-chip class="bg-cream text-ink border border-line/80">Preventiva</x-chip>
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold']) }}>{{ $slot }}</span>
