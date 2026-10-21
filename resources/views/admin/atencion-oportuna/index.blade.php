<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-[#2D2526]">Atención oportuna</h2>
                <p class="mt-1 text-xs text-[#8A7A7D]">Seguimiento a solicitudes urgentes de dolor y lista de espera para huecos de agenda.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-medium text-emerald-800 shadow-soft">
                    {{ session('success') }}
                </div>
            @endif

            <section class="overflow-hidden rounded-2xl border border-rose-200/80 bg-white shadow-soft">
                <div class="border-b border-rose-100 bg-rose-50/50 p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-rose-950">Solicitudes urgentes pendientes</h3>
                            <p class="mt-0.5 text-xs text-rose-700">Revisar y contactar a la brevedad; no representa un diagnóstico automático.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-[#e9dfdb]/60">
                    @forelse ($urgencias as $urgencia)
                        <article class="p-6 transition hover:bg-[#FAF6F6]/40">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 max-w-2xl">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-semibold text-[#2D2526]">{{ $urgencia->nombre }}</h4>
                                        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Urgente</span>
                                    </div>
                                    <p class="mt-1 text-sm font-medium text-brand-700">{{ $urgencia->telefono }} · {{ $urgencia->email ?: 'Sin correo' }}</p>
                                    <p class="mt-3 text-sm leading-relaxed text-[#2D2526] bg-[#FAF6F6] p-3 rounded-xl border border-[#e9dfdb]/60">{{ $urgencia->motivo }}</p>
                                    <p class="mt-2 text-xs text-[#8A7A7D]">Señales: <strong class="text-[#6b5e62]">{{ collect($urgencia->senales_reportadas)->join(', ') ?: 'No indicadas' }}</strong> · {{ $urgencia->created_at->diffForHumans() }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.atencion-oportuna.urgencias.contactar', $urgencia) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button>Marcar contactada</x-primary-button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="p-8 text-center text-sm text-[#8A7A7D]">No hay solicitudes urgentes pendientes.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-brand-200/80 bg-white shadow-soft">
                <div class="border-b border-brand-100 bg-brand-50/50 p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-brand-950">Lista de espera pendiente</h3>
                            <p class="mt-0.5 text-xs text-brand-700">Personas a quienes se puede ofrecer un horario que se liberó.</p>
                        </div>
                    </div>
                </div>
                <div class="divide-y divide-[#e9dfdb]/60">
                    @forelse ($espera as $solicitud)
                        <article class="p-6 transition hover:bg-[#FAF6F6]/40">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0 max-w-2xl">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-base font-semibold text-[#2D2526]">{{ $solicitud->nombre }}</h4>
                                        <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700 border border-brand-200/50">En espera</span>
                                    </div>
                                    <p class="mt-1 text-sm font-medium text-brand-700">{{ $solicitud->telefono }} · {{ $solicitud->email ?: 'Sin correo' }}</p>
                                    <p class="mt-2 text-sm font-semibold text-[#2D2526]">{{ $solicitud->servicio?->nombre ?? 'Cualquier servicio' }} · {{ $solicitud->tratamiento?->nombre ?? 'Cualquier tratamiento' }}</p>
                                    <p class="text-xs text-[#8A7A7D] mt-0.5">{{ $solicitud->fecha_preferida?->translatedFormat('d M Y') ?? 'Cualquier fecha' }} · {{ ucfirst($solicitud->franja_horaria) }}</p>
                                    @if ($solicitud->notas)
                                        <p class="mt-2 text-xs text-[#6b5e62] bg-[#FAF6F6] p-2.5 rounded-xl border border-[#e9dfdb]/60">{{ $solicitud->notas }}</p>
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
                        <p class="p-8 text-center text-sm text-[#8A7A7D]">No hay personas en lista de espera pendiente.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#e9dfdb] bg-white shadow-soft">
                <div class="border-b border-[#e9dfdb] bg-[#FAF6F6] p-6">
                    <h3 class="text-base font-semibold text-[#2D2526]">Historial de seguimiento</h3>
                    <p class="mt-1 text-xs text-[#8A7A7D]">Las solicitudes contactadas o cerradas se conservan y pueden consultarse por estado y fecha.</p>
                </div>
                <form method="GET" action="{{ route('admin.atencion-oportuna.index') }}" class="grid gap-4 p-6 sm:grid-cols-4 sm:items-end border-b border-[#e9dfdb]">
                    <div>
                        <x-input-label for="historial_estado" value="Estado" />
                        <select id="historial_estado" name="historial_estado" class="mt-1.5 block w-full rounded-xl border border-[#e9dfdb] bg-white px-3.5 py-2.5 text-sm text-[#2D2526] shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-200">
                            <option value="">Contactada y cerrada</option>
                            <option value="contactada" @selected(($historialFiltros['historial_estado'] ?? '') === 'contactada')>Contactada</option>
                            <option value="cerrada" @selected(($historialFiltros['historial_estado'] ?? '') === 'cerrada')>Cerrada</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="fecha_desde" value="Contactada desde" />
                        <x-text-input id="fecha_desde" name="fecha_desde" type="date" class="mt-1.5 block w-full" value="{{ $historialFiltros['fecha_desde'] ?? '' }}" />
                    </div>
                    <div>
                        <x-input-label for="fecha_hasta" value="Contactada hasta" />
                        <x-text-input id="fecha_hasta" name="fecha_hasta" type="date" class="mt-1.5 block w-full" value="{{ $historialFiltros['fecha_hasta'] ?? '' }}" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button>Filtrar historial</x-primary-button>
                        <a href="{{ route('admin.atencion-oportuna.index') }}"><x-secondary-button type="button">Limpiar</x-secondary-button></a>
                    </div>
                </form>

                <div class="grid gap-6 p-6 lg:grid-cols-2">
                    <div>
                        <h4 class="font-semibold text-[#2D2526] text-sm uppercase tracking-wider text-[#8A7A7D]">Urgencias atendidas</h4>
                        <div class="mt-3 divide-y divide-[#e9dfdb]/60">
                            @forelse ($urgenciasHistorial as $urgencia)
                                <article class="py-4 text-sm">
                                    <p class="font-semibold text-[#2D2526]">{{ $urgencia->nombre }} <span class="font-normal text-[#8A7A7D]">({{ ucfirst($urgencia->estado) }})</span></p>
                                    <p class="text-[#6b5e62]">{{ $urgencia->telefono }} · {{ $urgencia->email ?: 'Sin correo' }}</p>
                                    <p class="mt-1 text-[#2D2526]">{{ $urgencia->motivo }}</p>
                                    <p class="mt-1 text-xs text-[#8A7A7D]">Contacto: {{ $urgencia->fecha_contacto?->translatedFormat('d M Y, H:i') ?? 'Sin fecha' }}</p>
                                </article>
                            @empty
                                <p class="py-4 text-sm text-[#8A7A7D]">No hay urgencias en el historial con esos filtros.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-[#2D2526] text-sm uppercase tracking-wider text-[#8A7A7D]">Lista de espera atendida</h4>
                        <div class="mt-3 divide-y divide-[#e9dfdb]/60">
                            @forelse ($esperaHistorial as $solicitud)
                                <article class="py-4 text-sm">
                                    <p class="font-semibold text-[#2D2526]">{{ $solicitud->nombre }} <span class="font-normal text-[#8A7A7D]">({{ ucfirst($solicitud->estado) }})</span></p>
                                    <p class="text-[#6b5e62]">{{ $solicitud->telefono }} · {{ $solicitud->email ?: 'Sin correo' }}</p>
                                    <p class="mt-1 text-[#2D2526]">{{ $solicitud->servicio?->nombre ?? 'Cualquier servicio' }} · {{ $solicitud->tratamiento?->nombre ?? 'Cualquier tratamiento' }}</p>
                                    <p class="mt-1 text-xs text-[#8A7A7D]">Contacto: {{ $solicitud->fecha_contacto?->translatedFormat('d M Y, H:i') ?? 'Sin fecha' }}</p>
                                </article>
                            @empty
                                <p class="py-4 text-sm text-[#8A7A7D]">No hay personas en el historial con esos filtros.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
