{{--
    Acciones de una cita en la lista: el paso siguiente como botón principal
    (Confirmar, Iniciar o Completar) y "Más acciones" para reagendar, marcar
    inasistencia o cancelar. Espera $cita.
--}}
@php
    $estadosConAccionesSecundarias = ['agendada', 'confirmada'];
    $estadosTerminales = ['completada', 'cancelada', 'no_asistio'];
    $esPersonal = true;
    // Cita completada sin pago y quien registra pagos (24/11/2026).
    $puedeRegistrarPago = $cita->exists && $cita->id_paciente && $cita->estado->value === 'completada' && auth()->user()?->puede('pagos') && ! $cita->pagos()->exists();
    // Cita completada sin su consulta y quien ve expedientes (22/11/2026).
    $puedeRegistrarConsulta = $cita->exists && $cita->id_paciente && $cita->estado->value === 'completada' && auth()->user()?->puede('pacientes') && ! $cita->historialClinico()->exists();
@endphp
<div class="flex w-max max-w-full items-center justify-end gap-2">
    {{--
        QA 24/11/2026: si ya pasó la hora de fin y nadie la atendió en el sistema,
        ya no tiene sentido «Iniciar» ni «Confirmar»: se pregunta directo si fue.
    --}}
    @if ($esPersonal && $cita->exists && $cita->yaTermino())
        <form method="POST" action="{{ route('citas.complete', $cita) }}" data-cita-atendida data-confirmar="La cita quedará como atendida." data-confirmar-titulo="¿El paciente sí vino?" data-confirmar-boton="Sí, fue atendido" data-confirmar-tono="normal">
            @csrf
            @method('patch')
            <button type="submit"
                    class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-full bg-sage-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-sage-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-500 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 01.006 1.414l-7.25 7.3a1 1 0 01-1.42 0l-3.75-3.775a1 1 0 111.42-1.408l3.04 3.06 6.54-6.585a1 1 0 011.414-.006z" clip-rule="evenodd" /></svg>
                Atendida
            </button>
        </form>
        <form method="POST" action="{{ route('citas.no-show', $cita) }}" data-cita-no-asistio
              data-confirmar="Quedará en el historial del paciente como inasistencia." data-confirmar-titulo="¿El paciente no vino?" data-confirmar-boton="Sí, no asistió" data-confirmar-tono="peligro">
            @csrf
            @method('patch')
            <button type="submit"
                    class="inline-flex h-[42px] items-center justify-center whitespace-nowrap rounded-full border border-amber-200 bg-white px-3.5 text-xs font-semibold text-amber-800 shadow-2xs transition hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2">
                No asistió
            </button>
        </form>
    @elseif ($esPersonal && $cita->estado->value === 'agendada')
        <form method="POST" action="{{ route('citas.confirm', $cita) }}" data-confirmar="Se avisará al paciente que su cita quedó confirmada." data-confirmar-titulo="¿Confirmar la cita?" data-confirmar-boton="Sí, confirmar" data-confirmar-tono="normal">
            @csrf
            @method('patch')
            <button type="submit"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                Confirmar
            </button>
        </form>
    @elseif ($esPersonal && $cita->estado->value === 'confirmada' && $cita->sePuedeIniciar())
        {{-- Solo el día de la cita (QA 24/11/2026: salía también en días futuros). --}}
        <form method="POST" action="{{ route('citas.start', $cita) }}" data-confirmar="La cita pasará a «En curso»." data-confirmar-titulo="¿Iniciar la cita?" data-confirmar-boton="Sí, iniciar" data-confirmar-tono="normal">
            @csrf
            @method('patch')
            <button type="submit"
                    class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-full bg-brand-600 px-3.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.5 4.75a.75.75 0 011.143-.64l8 5a.75.75 0 010 1.28l-8 5A.75.75 0 016.5 14.75v-10z" /></svg>
                Iniciar
            </button>
        </form>
    @elseif ($esPersonal && $cita->estado->value === 'en_curso')
        <form method="POST" action="{{ route('citas.complete', $cita) }}" data-confirmar="La cita quedará como completada." data-confirmar-titulo="¿Completar la cita?" data-confirmar-boton="Sí, completar" data-confirmar-tono="normal">
            @csrf
            @method('patch')
            <button type="submit"
                    class="inline-flex items-center justify-center whitespace-nowrap rounded-full bg-sage-600 px-4 py-2.5 text-xs font-semibold text-white shadow-2xs transition hover:bg-sage-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-500 focus-visible:ring-offset-2">
                Completar
            </button>
        </form>
    @endif

    {{--
        Cita completada sin su consulta (22/11/2026): completar la cita no escribe la nota
        médica; este botón abre "Nueva consulta" con la cita y su fecha ya elegidas.
        Solo para quien ve expedientes (módulo "pacientes" de config/permisos.php).
    --}}
    @if ($puedeRegistrarConsulta)
        <a href="{{ route('admin.pacientes.historial.create', [$cita->id_paciente, 'id_cita' => $cita->id]) }}" data-registrar-consulta
           class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-full border border-brand-200 bg-white px-3.5 text-xs font-semibold text-brand-700 shadow-2xs transition hover:border-brand-400 hover:bg-brand-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2">
            <x-icon name="expediente" />
            Registrar consulta
        </a>
    @endif

    @if ($puedeRegistrarPago)
        <a href="{{ route('admin.pagos.create', ['paciente' => $cita->id_paciente, 'cita' => $cita->id]) }}" data-registrar-pago
           class="inline-flex h-[42px] items-center justify-center gap-1.5 whitespace-nowrap rounded-full border border-sage-200 bg-white px-3.5 text-xs font-semibold text-sage-700 shadow-2xs transition hover:border-sage-400 hover:bg-sage-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-400 focus-visible:ring-offset-2">
            $ Registrar pago
        </a>
    @endif

    @if ($esPersonal && in_array($cita->estado->value, $estadosConAccionesSecundarias, true))
        <details class="group relative">
            <summary aria-label="Abrir más acciones de la cita" title="Más acciones" class="inline-flex h-[42px] cursor-pointer list-none items-center justify-center gap-2 rounded-full border border-line bg-white px-3 text-xs font-semibold text-ink-soft shadow-2xs transition-colors duration-150 hover:border-peach-300 hover:text-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 [&::-webkit-details-marker]:hidden">
                <span class="flex h-6 w-6 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 4.25a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5zm0 4.5a1.25 1.25 0 110 2.5 1.25 1.25 0 010-2.5z" />
                    </svg>
                </span>
                Más acciones
                <svg class="h-3.5 w-3.5 text-muted transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 011.06 0L10 10.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 8.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
            </summary>
            <div x-data="{}" x-init="$nextTick(() => { const detail = $el.closest('details'); detail?.addEventListener('toggle', () => { if (!detail.open) return; const trigger = detail.querySelector('summary').getBoundingClientRect(); const scrollArea = detail.closest('.overflow-x-auto')?.getBoundingClientRect(); const top = scrollArea?.top ?? 0; const bottom = scrollArea?.bottom ?? window.innerHeight; const roomBelow = bottom - trigger.bottom; const roomAbove = trigger.top - top; $el.classList.toggle('bottom-full', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('top-full', !(roomBelow < 220 && roomAbove > roomBelow)); $el.classList.toggle('mb-2', roomBelow < 220 && roomAbove > roomBelow); $el.classList.toggle('mt-2', !(roomBelow < 220 && roomAbove > roomBelow)); }); })" class="menu-acciones absolute right-0 top-full z-30 mt-2 flex min-w-48 flex-col rounded-2xl border border-line bg-white p-1.5 shadow-lift">
                <p class="px-3 pb-2 pt-1 text-caption font-semibold uppercase tracking-label text-muted">Gestionar cita</p>
                {{-- Misma regla que CitaController@noShow: solo después de la hora de la cita.
                     Si ya terminó, el botón está a la vista y no se repite aquí. --}}
                @if (in_array($cita->estado->value, ['agendada', 'confirmada'], true) && ! $cita->fecha_hora->isFuture() && ! $cita->yaTermino())
                    <form method="POST" action="{{ route('citas.no-show', $cita) }}"
                          data-confirmar="Esta acción no se puede deshacer." data-confirmar-titulo="¿Marcar esta cita como no asistida?" data-confirmar-boton="Sí, marcar" data-confirmar-tono="peligro">
                        @csrf
                        @method('patch')
                        <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-ink-soft transition hover:bg-amber-50 hover:text-amber-800 focus:bg-amber-50 focus:outline-none">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-11.25a.75.75 0 00-1.5 0v3.5c0 .414.336.75.75.75h2a.75.75 0 000-1.5h-1.25v-2.75z" clip-rule="evenodd" /></svg>
                            </span>
                            Marcar inasistencia
                        </button>
                    </form>
                @endif
                <a href="{{ route('citas.edit', $cita) }}" class="flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:bg-brand-50 hover:text-brand-800 focus:bg-brand-50 focus:text-brand-800 focus:outline-none">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M10 4v2m0 8v2m6-6h-2M6 10H4m10.243-4.243-1.414 1.414M7.172 12.828l-1.414 1.414m8.485 0-1.414-1.414M7.172 7.172 5.758 5.758" /><circle cx="10" cy="10" r="3" stroke-width="1.6" /></svg>
                    </span>
                    Reagendar
                </a>
                <div class="mx-2 my-1 border-t border-line"></div>
                <form method="POST" action="{{ route('citas.cancel', $cita) }}"
                      data-confirmar="El horario quedará libre y se avisará al paciente." data-confirmar-titulo="¿Cancelar esta cita?"
                      data-confirmar-boton="Sí, cancelar cita" data-confirmar-tono="peligro"
                      data-confirmar-motivo="motivo_cancelacion" data-confirmar-motivo-etiqueta="Motivo de la cancelación (opcional)">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="motivo_cancelacion" value="">
                    <button type="submit" class="flex w-full items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 20 20" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m6 6 8 8M14 6l-8 8" /></svg>
                        </span>
                        Cancelar cita
                    </button>
                </form>
            </div>
        </details>
    @elseif ($esPersonal && in_array($cita->estado->value, $estadosTerminales, true) && ! $puedeRegistrarConsulta && ! $puedeRegistrarPago)
        <span class="text-xs text-muted">Sin acciones</span>
    @endif
</div>
