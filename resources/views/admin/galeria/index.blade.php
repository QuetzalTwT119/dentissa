<x-app-layout>
    <x-slot name="header">
        <x-admin.header
            badge="Instalaciones y Espacios"
            title="Galería"
            subtitle="Fotografías de las instalaciones, equipo e infraestructura de la clínica."
        >
            <x-slot:actions>
                <a href="{{ route('admin.galeria.create') }}">
                    <x-primary-button class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva imagen
                    </x-primary-button>
                </a>
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-admin.card>
                <x-admin.card-header
                    title="Fotografías de la clínica"
                    description="Fotos del consultorio visibles para pacientes y personal."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.galeria.create') }}" class="hidden sm:inline-block">
                            <x-secondary-button>Nueva imagen</x-secondary-button>
                        </a>
                    </x-slot:actions>
                </x-admin.card-header>

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
                                            <x-admin.status-badge :active="$imagen->activa" />
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4 pt-0">
                                    <div class="flex items-center justify-between border-t border-line/60 pt-3 text-xs font-semibold">
                                        <a href="{{ route('admin.galeria.edit', $imagen) }}" class="text-brand-600 hover:text-brand-800 transition">Editar</a>
                                        <form method="POST" action="{{ route('admin.galeria.destroy', $imagen) }}"
                                              onsubmit="return confirm('¿Eliminar esta imagen de la galería? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 transition">Eliminar</button>
                                        </form>
                                    </div>
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

                <x-pagination-summary :items="$imagenes" label="imágenes" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
