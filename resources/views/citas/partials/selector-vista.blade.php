{{-- Selector Lista | Semana. Ambas vistas viven en /citas para no agregar otro menú. --}}
<nav aria-label="Vista de la agenda" data-vista-actual="{{ $vistaActual }}" class="flex">
    <div class="inline-flex rounded-full border border-line bg-white p-1 shadow-2xs">
        @foreach (['lista' => ['Lista', route('citas.index')], 'semana' => ['Semana', route('citas.index', ['vista' => 'semana'])]] as $clave => [$etiqueta, $url])
            <a href="{{ $url }}"
               @if ($vistaActual === $clave) aria-current="page" @endif
               @class([
                   'inline-flex min-h-10 items-center rounded-full px-5 text-sm font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400',
                   'bg-brand-600 text-white' => $vistaActual === $clave,
                   'text-muted hover:bg-cream hover:text-brand-700' => $vistaActual !== $clave,
               ])>{{ $etiqueta }}</a>
        @endforeach
    </div>
</nav>
