@php
$tipoConsultaLabel = [
    'primera_vez' => 'Primera vez',
    'seguimiento' => 'Seguimiento',
    'urgencia' => 'Urgencia',
    'control' => 'Control',
];
@endphp

<x-app-layout>
    @php
        $hayPdf = $consultas->isNotEmpty() || $paciente->historiaClinica || $paciente->exploracionesClinicas()->exists();
        $iconoPdf = '<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>';
    @endphp
    <x-slot name="header">
        {{-- 23/11/2026: "Volver a Pacientes" global arriba del título; el PDF arriba (es de todo el
             expediente) y "Nueva consulta" en la sección Consultas, como las demás secciones. --}}
        <x-admin.header :volver="route('admin.pacientes.index')" volver-texto="Volver a Pacientes" title="Expediente de {{ $paciente->nombre }} {{ $paciente->apellido }}"
                        subtitle="Historia clínica, exploración con odontograma y consultas.">
            <x-slot:actions>
                @if ($hayPdf)
                    <a href="{{ route('admin.pacientes.historial.pdf', $paciente) }}">
                        <x-secondary-button type="button" class="inline-flex items-center gap-2">{!! $iconoPdf !!} Descargar PDF</x-secondary-button>
                    </a>
                @else
                    <x-secondary-button type="button" disabled title="Disponible cuando haya historia clínica, exploración o una consulta" class="inline-flex cursor-not-allowed items-center gap-2 opacity-50">{!! $iconoPdf !!} Descargar PDF</x-secondary-button>
                @endif
            </x-slot:actions>
        </x-admin.header>
    </x-slot>

    <div class="panel-pagina py-8 sm:py-10">
        {{-- Mismo ancho que el encabezado: título, PDF y tarjetas alineados. --}}
        <div class="panel-contenido mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <x-alert type="info" :message="session('status')" />
            @endif

            {{-- Historia clínica (cuestionario, 22/11/2026): primera sección del expediente. --}}
            @php $historia = $paciente->historiaClinica; @endphp
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-historia" data-resumen-historia>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="titulo-historia" class="text-headline font-semibold text-ink">Historia clínica</h2>
                        <p class="mt-0.5 text-sm text-muted">
                            @if ($historia)
                                Antecedentes, alergias y hábitos del paciente · actualizada {{ $historia->updated_at?->translatedFormat('j M Y') }}
                            @else
                                Aún no tiene historia clínica: llénala en la primera visita.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('admin.pacientes.historia.edit', $paciente) }}" class="{{ $historia ? 'btn-secondary' : 'btn-brand' }} min-h-11 px-5" data-llenar-historia>
                        {{ $historia ? 'Ver o editar' : 'Llenar cuestionario' }}
                    </a>
                </div>

                @if ($historia)
                    <x-aviso-clinico :historia="$historia" class="mt-4" />
                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm sm:grid-cols-4">
                        <div><dt class="text-muted">Edad</dt><dd class="font-semibold text-ink">{{ $historia->edad() !== null ? $historia->edad().' años' : '—' }}</dd></div>
                        <div><dt class="text-muted">Sexo</dt><dd class="font-semibold text-ink">{{ \App\Models\HistoriaClinica::SEXOS[$historia->sexo] ?? '—' }}</dd></div>
                        <div><dt class="text-muted">Tipo de sangre</dt><dd class="font-semibold text-ink">{{ $historia->tipo_sangre ?? '—' }}</dd></div>
                        <div><dt class="text-muted">Medicamentos</dt><dd class="font-semibold text-ink">{{ filled($historia->medicamentos_actuales) ? \Illuminate\Support\Str::limit($historia->medicamentos_actuales, 40) : 'Ninguno' }}</dd></div>
                    </dl>
                @endif
            </section>

            {{-- Exploración clínica y odontograma (23/11/2026). --}}
            @php
                $exploraciones = $paciente->exploracionesClinicas()->with('doctor')->get();
                $ultima = $exploraciones->first();
            @endphp
            <section class="rounded-3xl border border-line bg-white p-5 shadow-soft sm:p-6" aria-labelledby="titulo-exploracion" data-resumen-exploracion>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="titulo-exploracion" class="text-headline font-semibold text-ink">Exploración clínica y odontograma</h2>
                        <p class="mt-0.5 text-sm text-muted">
                            @if ($ultima)
                                Última: {{ $ultima->fecha->translatedFormat('j M Y') }}{{ $ultima->doctor ? ' · '.$ultima->doctor->nombre.' '.$ultima->doctor->apellido : '' }}
                            @else
                                Aún no tiene exploración: examen de la boca, encías, higiene y odontograma.
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($ultima)
                            <a href="{{ route('admin.pacientes.exploracion.edit', [$paciente, $ultima]) }}" class="btn-secondary min-h-11 px-5">Ver o editar</a>
                        @endif
                        <a href="{{ route('admin.pacientes.exploracion.create', $paciente) }}" class="{{ $ultima ? 'btn-secondary' : 'btn-brand' }} min-h-11 px-5" data-nueva-exploracion>
                            {{ $ultima ? 'Nueva exploración' : 'Hacer exploración' }}
                        </a>
                    </div>
                </div>

                @if ($ultima)
                    @php
                        $nombresEstado = collect(\App\Models\ExploracionClinica::ESTADOS_DIENTE)->map(fn ($e) => $e[0]);
                        $resumen = collect($ultima->resumenOdontograma())->map(fn ($n, $e) => ($nombresEstado[$e] ?? $e).': '.$n)->implode(' · ');
                    @endphp
                    <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-3">
                        <div><dt class="text-muted">Higiene oral</dt><dd class="font-semibold text-ink">{{ \App\Models\ExploracionClinica::HIGIENE[$ultima->higiene_oral] ?? '—' }}</dd></div>
                        <div><dt class="text-muted">Índice de O'Leary</dt><dd @class(['font-semibold', 'text-sage-700' => $ultima->indiceOleary() !== null && $ultima->indiceOleary() <= 20, 'text-rose-700' => $ultima->indiceOleary() > 20, 'text-ink' => $ultima->indiceOleary() === null])>{{ $ultima->indiceOleary() !== null ? $ultima->indiceOleary().' %' : '—' }}</dd></div>
                        <div><dt class="text-muted">Odontograma</dt><dd class="font-semibold text-ink">{{ $resumen ?: 'Sin marcar' }}</dd></div>
                    </dl>
                    @if (filled($ultima->diagnosticos))
                        <p class="mt-3 text-sm text-ink-soft"><span class="font-semibold text-ink">Diagnósticos:</span> {{ \Illuminate\Support\Str::limit($ultima->diagnosticos, 160) }}</p>
                    @endif

                    @if ($exploraciones->count() > 1)
                        {{-- Historial del índice de placa, como en el formato en papel. --}}
                        <details class="mt-4 rounded-2xl border border-line" data-exploraciones-anteriores>
                            <summary class="flex min-h-11 cursor-pointer items-center px-4 text-sm font-semibold text-ink">Exploraciones anteriores ({{ $exploraciones->count() - 1 }})</summary>
                            <ul class="divide-y divide-line border-t border-line">
                                @foreach ($exploraciones->skip(1) as $anterior)
                                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                                        <span class="text-ink">{{ $anterior->fecha->translatedFormat('j M Y') }} · O'Leary {{ $anterior->indiceOleary() !== null ? $anterior->indiceOleary().' %' : '—' }}</span>
                                        <a href="{{ route('admin.pacientes.exploracion.edit', [$paciente, $anterior]) }}" class="link inline-flex min-h-11 items-center">Ver</a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                @endif
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-headline font-semibold text-ink">Consultas</h2>
                <a href="{{ route('admin.pacientes.historial.create', $paciente) }}" class="{{ $consultas->isEmpty() ? 'btn-brand' : 'btn-secondary' }} min-h-11 px-5" data-nueva-consulta>
                    Nueva consulta
                </a>
            </div>
            <x-pagination-summary :items="$consultas" label="consultas" :integrated="false" />

            <div class="space-y-4">
                @forelse ($consultas as $consulta)
                    <div class="rounded-2xl border border-line bg-white p-6 shadow-soft transition hover:border-peach-300">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-chip class="bg-brand-50 text-brand-700 border border-brand-200/50">
                                        {{ $tipoConsultaLabel[$consulta->tipo_consulta->value] ?? $consulta->tipo_consulta->value }}
                                    </x-chip>
                                    <p class="text-xs text-muted">
                                        {{ \Illuminate\Support\Carbon::parse($consulta->fecha_consulta)->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <h3 class="mt-2 text-headline font-semibold text-ink">{{ $consulta->motivo_consulta }}</h3>
                                @if ($consulta->diagnostico)
                                    <p class="mt-1 text-sm text-ink-soft">
                                        <span class="font-semibold text-ink">Diagnóstico:</span> {{ $consulta->diagnostico }}
                                    </p>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted">
                                    <span>Doctor: <strong class="text-ink font-medium">{{ $consulta->doctor->nombre ?? '' }} {{ $consulta->doctor->apellido ?? '' }}</strong></span>
                                    @if ($consulta->imagenes->isNotEmpty())
                                        <span>· <strong class="text-ink font-medium">{{ $consulta->imagenes->count() }}</strong> adjunto(s)</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 pt-2 sm:pt-0 border-t border-line/60 sm:border-0">
                                <a href="{{ route('admin.pacientes.historial.edit', [$paciente, $consulta]) }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-brand-600 transition hover:bg-brand-50 hover:text-brand-800">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('admin.pacientes.historial.destroy', [$paciente, $consulta]) }}"
                                      data-confirmar="Esta acción no se puede deshacer." data-confirmar-titulo="¿Eliminar esta consulta del historial?" data-confirmar-boton="Sí, eliminar" data-confirmar-tono="peligro">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-800">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-line bg-white p-10 text-center text-sm text-muted shadow-soft">
                        <p class="font-semibold text-ink">Este paciente todavía no tiene consultas registradas.</p>
                        <p class="mt-1">Registra la primera con «Nueva consulta», o desde la Agenda al completar su cita.</p>
                    </div>
                @endforelse
            </div>
            <x-pagination-summary :items="$consultas" label="consultas" :integrated="false" posicion="abajo" />
        </div>
    </div>
</x-app-layout>
