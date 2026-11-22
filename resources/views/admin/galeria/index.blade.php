<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Instalaciones y Espacios"
            title="Galería"
            subtitle="Fotografías de las instalaciones, equipo e infraestructura de la clínica."
        >
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>

                <x-pagination-summary :items="$imagenes" label="imágenes">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.galeria.index')" :campos="['buscar', 'activa']">
                            <x-filtro.buscar name="buscar" etiqueta="Buscar imágenes" placeholder="Título de la imagen…" />
                            <x-filtro.select name="activa" etiqueta="Filtrar por estado" todos="Todos los estados" :opciones="['1' => 'Activas', '0' => 'Inactivas']" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                    <x-slot:acciones>
                        <a href="{{ route('admin.galeria.create') }}" class="btn-brand min-h-11 px-5">
                            <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"> <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /> </svg>
                            Nueva imagen
                        </a>
                    </x-slot:acciones>
                </x-pagination-summary>

                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 items-stretch">
                        @forelse ($imagenes as $imagen)
                            <div class="overflow-hidden rounded-2xl border border-line bg-white shadow-xs transition hover:border-peach-300 group h-full flex flex-col justify-between">
                                <div>
                                    <div class="relative h-44 w-full overflow-hidden bg-stone-100">
                                        <img src="/storage/{{ $imagen->url }}" alt="{{ $imagen->titulo ?? '' }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                                    </div>
                                    <div class="p-4 pb-2">
                                        <h3 class="truncate text-sm font-semibold text-ink">{{ $imagen->titulo ?: 'Sin título' }}</h3>
                                        <div class="mt-1.5 flex items-center justify-between text-xs text-muted">
                                            <span>Orden: <strong class="text-ink">{{ $imagen->orden }}</strong></span>
                                            <x-admin.interruptor-estado :action="route('admin.galeria.estado', $imagen)" :activo="$imagen->activa" :nombre="$imagen->titulo ?: 'Foto'"
                                                                        textoActivo="Activa" textoInactivo="Inactiva"
                                                                        desactivar="Dejará de mostrarse en la galería." activar="Se mostrará en la galería." />
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 pt-0">
                                    {{-- Acciones globales (22/11/2026; antes enlaces de texto propios). --}}
                                    <x-admin.acciones-fila class="border-t border-line/60 pt-3" :editar="route('admin.galeria.edit', $imagen)" :eliminar="route('admin.galeria.destroy', $imagen)"
                                                           que="la imagen" :nombre="$imagen->titulo" confirmar-titulo="¿Eliminar esta imagen de la galería?" />
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-line bg-cream/40 p-12 text-center text-sm text-muted">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 border border-brand-100">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h4 class="font-semibold text-ink">No hay imágenes en la galería todavía</h4>
                                <p class="mt-1 text-xs">Sube fotos de las instalaciones, equipo y espacios de la clínica.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <x-pagination-summary :items="$imagenes" label="imágenes" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
