{{--
    Pagos e ingresos (24/11/2026): lo cobrado en un periodo (por omisión, el mes
    en curso), con total por forma de pago y la lista de pagos.
--}}
@use('App\Models\Pago')
<x-app-layout>
    <x-slot name="header">
        <x-admin.header badge="Gestión" title="Pagos e ingresos" subtitle="Lo que se cobró en el consultorio, por periodo y forma de pago." />
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        <div class="panel-contenido mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            {{-- Totales del periodo --}}
            <section aria-label="Totales del periodo" class="grid grid-cols-2 gap-4 lg:grid-cols-4" data-totales-ingresos>
                <div class="rounded-3xl border border-line bg-white p-5 shadow-soft">
                    <p class="text-xs font-semibold uppercase tracking-label text-muted">Total cobrado</p>
                    <p class="mt-1 text-title-2 text-ink" data-total-ingresos>{{ Pago::dinero($total) }}</p>
                    <p class="mt-0.5 text-xs text-muted">{{ number_format($cantidad) }} {{ $cantidad === 1 ? 'pago' : 'pagos' }}</p>
                </div>
                @foreach (Pago::METODOS as $clave => $texto)
                    <div class="rounded-3xl border border-line bg-white p-5 shadow-soft">
                        <p class="text-xs font-semibold uppercase tracking-label text-muted">{{ $texto }}</p>
                        <p class="mt-1 text-headline font-semibold text-ink">{{ Pago::dinero($porMetodo[$clave]->total ?? 0) }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ (int) ($porMetodo[$clave]->pagos ?? 0) }} {{ (int) ($porMetodo[$clave]->pagos ?? 0) === 1 ? 'pago' : 'pagos' }}</p>
                    </div>
                @endforeach
            </section>

            <x-admin.card>
                <x-pagination-summary :items="$pagos" label="pagos">
                    <x-slot:filtros>
                        <x-filtro.barra :action="route('admin.pagos.index')" :campos="['desde', 'hasta', 'metodo']">
                            <x-filtro.fecha name="desde" etiqueta="Desde" :valor="$desde" :max="now()->toDateString()" />
                            <x-filtro.fecha name="hasta" etiqueta="Hasta" :valor="$hasta" :max="now()->toDateString()" />
                            <x-filtro.select name="metodo" etiqueta="Forma de pago" todos="Todas las formas" :opciones="Pago::METODOS" />
                        </x-filtro.barra>
                    </x-slot:filtros>
                </x-pagination-summary>
                <x-admin.table minWidth="min-w-[760px]">
                    <x-slot:head>
                        <tr>
                            <th class="px-6 py-4">Fecha</th>
                            <th class="px-6 py-4">Paciente</th>
                            <th class="px-6 py-4">Concepto</th>
                            <th class="px-6 py-4">Forma</th>
                            <th class="px-6 py-4 text-right">Monto</th>
                            <th class="px-6 py-4 text-right">Acciones</th>
                        </tr>
                    </x-slot:head>

                    @forelse ($pagos as $pago)
                        <tr class="transition-colors hover:bg-brand-50/20" data-pago>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-ink-soft">{{ $pago->fecha->translatedFormat('j M Y') }}</td>
                            <td class="px-6 py-4">
                                @if ($pago->paciente && auth()->user()->puede('pacientes'))
                                    <a href="{{ route('admin.pacientes.historial.index', $pago->paciente) }}" class="font-semibold text-ink hover:text-brand-700">{{ $pago->paciente->nombre_completo }}</a>
                                @else
                                    <span class="font-semibold text-ink">{{ $pago->paciente?->nombre_completo ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-ink-soft">{{ $pago->concepto ?: '—' }}</td>
                            <td class="px-6 py-4 text-sm text-ink-soft">{{ $pago->metodoTexto() }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-ink tabular-nums">{{ Pago::dinero($pago->monto) }}</td>
                            <td class="px-6 py-4 text-right">
                                <x-admin.acciones-fila :eliminar="route('admin.pagos.destroy', $pago)" que="el pago" :nombre="Pago::dinero($pago->monto).' de '.($pago->paciente?->nombre_completo ?? 'paciente')"
                                                       confirmar-titulo="¿Eliminar este pago?" />
                            </td>
                        </tr>
                    @empty
                        <x-admin.empty-state colspan="6" title="No hay pagos en este periodo">
                            <p class="mt-1 text-xs text-muted">Los pagos se registran desde el expediente del paciente o desde una cita completada en la Agenda.</p>
                        </x-admin.empty-state>
                    @endforelse
                </x-admin.table>
                <x-pagination-summary :items="$pagos" label="pagos" posicion="abajo" />
            </x-admin.card>
        </div>
    </div>
</x-app-layout>
