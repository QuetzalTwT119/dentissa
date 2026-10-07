<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Atención oportuna</h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 bg-gray-50 p-6">
                    <h3 class="text-lg font-semibold text-gray-900">Historial de seguimiento</h3>
                    <p class="mt-1 text-sm text-gray-600">Las solicitudes contactadas o cerradas se conservan y pueden consultarse por estado y fecha.</p>
                </div>
                <form method="GET" action="{{ route('admin.atencion-oportuna.index') }}" class="grid gap-4 p-6 sm:grid-cols-4 sm:items-end">
                    <div>
                        <x-input-label for="historial_estado" value="Estado" />
                        <select id="historial_estado" name="historial_estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            <option value="">Contactada y cerrada</option>
                            <option value="contactada" @selected(($historialFiltros['historial_estado'] ?? '') === 'contactada')>Contactada</option>
                            <option value="cerrada" @selected(($historialFiltros['historial_estado'] ?? '') === 'cerrada')>Cerrada</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="fecha_desde" value="Contactada desde" />
                        <x-text-input id="fecha_desde" name="fecha_desde" type="date" class="mt-1 block w-full" value="{{ $historialFiltros['fecha_desde'] ?? '' }}" />
                    </div>
                    <div>
                        <x-input-label for="fecha_hasta" value="Contactada hasta" />
                        <x-text-input id="fecha_hasta" name="fecha_hasta" type="date" class="mt-1 block w-full" value="{{ $historialFiltros['fecha_hasta'] ?? '' }}" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button>Filtrar historial</x-primary-button>
                        <a href="{{ route('admin.atencion-oportuna.index') }}"><x-secondary-button type="button">Limpiar</x-secondary-button></a>
                    </div>
                </form>

                <div class="grid gap-6 border-t border-gray-100 p-6 lg:grid-cols-2">
                    <div>
                        <h4 class="font-semibold text-gray-900">Urgencias atendidas</h4>
                        <div class="mt-3 divide-y divide-gray-100">
                            @forelse ($urgenciasHistorial as $urgencia)
                                <article class="py-4 text-sm">
                                    <p class="font-semibold">{{ $urgencia->nombre }} <span class="font-normal text-gray-500">({{ ucfirst($urgencia->estado) }})</span></p>
                                    <p class="text-gray-600">{{ $urgencia->telefono }} · {{ $urgencia->email ?: 'Sin correo' }}</p>
                                    <p class="mt-1 text-gray-800">{{ $urgencia->motivo }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Contacto: {{ $urgencia->fecha_contacto?->translatedFormat('d M Y, H:i') ?? 'Sin fecha' }}</p>
                                </article>
                            @empty
                                <p class="py-4 text-sm text-gray-600">No hay urgencias en el historial con esos filtros.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900">Lista de espera atendida</h4>
                        <div class="mt-3 divide-y divide-gray-100">
                            @forelse ($esperaHistorial as $solicitud)
                                <article class="py-4 text-sm">
                                    <p class="font-semibold">{{ $solicitud->nombre }} <span class="font-normal text-gray-500">({{ ucfirst($solicitud->estado) }})</span></p>
                                    <p class="text-gray-600">{{ $solicitud->telefono }} · {{ $solicitud->email ?: 'Sin correo' }}</p>
                                    <p class="mt-1 text-gray-800">{{ $solicitud->servicio?->nombre ?? 'Cualquier servicio' }} · {{ $solicitud->tratamiento?->nombre ?? 'Cualquier tratamiento' }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Contacto: {{ $solicitud->fecha_contacto?->translatedFormat('d M Y, H:i') ?? 'Sin fecha' }}</p>
                                </article>
                            @empty
                                <p class="py-4 text-sm text-gray-600">No hay personas en el historial con esos filtros.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-red-100 bg-red-50 p-6">
                    <h3 class="text-lg font-semibold text-red-900">Solicitudes urgentes pendientes</h3>
                    <p class="mt-1 text-sm text-red-800">Revisar y contactar; no representa un diagnóstico automático.</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($urgencias as $urgencia)
                        <article class="p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $urgencia->nombre }}</p>
                                    <p class="text-sm text-gray-600">{{ $urgencia->telefono }} · {{ $urgencia->email ?: 'Sin correo' }}</p>
                                    <p class="mt-3 text-sm text-gray-800">{{ $urgencia->motivo }}</p>
                                    <p class="mt-2 text-xs text-gray-500">Señales: {{ collect($urgencia->senales_reportadas)->join(', ') ?: 'No indicadas' }} · {{ $urgencia->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.atencion-oportuna.urgencias.contactar', $urgencia) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Marcar contactada</x-primary-button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="p-6 text-sm text-gray-600">No hay solicitudes urgentes pendientes.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="border-b border-brand-100 bg-brand-50 p-6">
                    <h3 class="text-lg font-semibold text-brand-900">Lista de espera pendiente</h3>
                    <p class="mt-1 text-sm text-brand-800">Personas a quienes se puede ofrecer un horario que se liberó.</p>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($espera as $solicitud)
                        <article class="p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold">{{ $solicitud->nombre }}</p>
                                    <p class="text-sm text-gray-600">{{ $solicitud->telefono }} · {{ $solicitud->email ?: 'Sin correo' }}</p>
                                    <p class="mt-2 text-sm text-gray-800">{{ $solicitud->servicio?->nombre ?? 'Cualquier servicio' }} · {{ $solicitud->tratamiento?->nombre ?? 'Cualquier tratamiento' }}</p>
                                    <p class="text-sm text-gray-600">{{ $solicitud->fecha_preferida?->translatedFormat('d M Y') ?? 'Cualquier fecha' }} · {{ ucfirst($solicitud->franja_horaria) }}</p>
                                    @if ($solicitud->notas)
                                        <p class="mt-2 text-sm text-gray-600">{{ $solicitud->notas }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.atencion-oportuna.espera.contactar', $solicitud) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Marcar contactada</x-primary-button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="p-6 text-sm text-gray-600">No hay personas en lista de espera pendiente.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
