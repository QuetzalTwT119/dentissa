<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Services\AuditoriaService;
use App\Services\CitaReglasService;
use App\Services\DisponibilidadService;
use App\Services\NotificacionInternaService;
use App\Services\NotificacionService;
use App\Services\OcupacionSemanalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class CitaController extends Controller
{
    /**
     * Lista de citas.
     *
     * - Por día (predeterminado): una pestaña por día de la semana de ?fecha
     *   (hoy si no viene). Muestra todas las citas de ese día en orden de hora.
     * - Todas las fechas: al buscar (?buscar) o con ?todas=1, resultados de
     *   cualquier fecha, del más reciente al más antiguo, paginados. Así se
     *   sigue encontrando el historial de un paciente.
     */
    public function index(Request $request): View
    {
        if ($request->query('vista') === 'semana') {
            return $this->semana($request);
        }

        $validated = $this->filtros($request, [
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::enum(EstadoCita::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
            'todas' => ['nullable', 'boolean'],
        ]);

        $filtros = array_intersect_key($validated, array_flip(['fecha', 'estado', 'buscar']));
        $estado = $validated['estado'] ?? null;

        if (filled($validated['buscar'] ?? null) || $request->boolean('todas')) {
            return view('citas.index', [
                'citas' => $this->buscarCitas($validated['buscar'] ?? '', $estado)
                    ->orderByDesc('fecha_hora')
                    ->paginate($this->paginationPageSize($request))
                    ->withQueryString(),
                'filtros' => $filtros,
            ]);
        }

        $dia = isset($validated['fecha']) ? Carbon::parse($validated['fecha'])->startOfDay() : Carbon::today();

        return view('citas.index', [
            'dia' => $dia,
            'citasDelDia' => Cita::with(['paciente', 'servicio', 'tratamiento', 'oferta', 'citaNueva'])
                ->whereDate('fecha_hora', $dia)
                ->when($estado, fn ($q) => $q->where('estado', $estado))
                ->orderBy('fecha_hora')
                ->get(),
            'pestanas' => $this->pestanasDeLaSemana($dia),
            'semanaAnterior' => $dia->copy()->subWeek()->toDateString(),
            'semanaSiguiente' => $dia->copy()->addWeek()->toDateString(),
            'filtros' => $filtros,
            // QA 24/11/2026: citas que se quedaron «En curso» de días anteriores
            // (nadie presionó Completar). La agenda avisa para cerrarlas.
            'enCursoOlvidadas' => Cita::with('paciente:id,nombre,apellido')
                ->where('estado', EstadoCita::EnCurso)
                ->whereDate('fecha_hora', '<', Carbon::today())
                ->orderBy('fecha_hora')
                ->get(['id', 'id_paciente', 'fecha_hora']),
        ]);
    }

    /** Búsqueda por paciente (nombre, teléfono, correo), servicio o tratamiento. */
    private function buscarCitas(string $buscar, ?string $estado)
    {
        $terminos = preg_split('/\s+/', trim($buscar), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Cita::with(['paciente', 'servicio', 'tratamiento', 'oferta', 'citaNueva'])
            ->when($estado, fn ($q) => $q->where('estado', $estado))
            ->when($terminos !== [], function ($q) use ($terminos) {
                foreach ($terminos as $termino) {

                    $q->where(function ($buscar) use ($termino) {
                        $buscar->whereHas('paciente', fn ($paciente) => $paciente
                            ->whereContiene('nombre', $termino)
                            ->orWhereContiene('apellido', $termino)
                            ->orWhereContiene('telefono', $termino)
                            ->orWhereContiene('email', $termino))
                            ->orWhereHas('servicio', fn ($servicio) => $servicio->whereContiene('nombre', $termino))
                            ->orWhereHas('tratamiento', fn ($tratamiento) => $tratamiento->whereContiene('nombre', $termino));
                    });
                }
            });
    }

    /**
     * Pestañas de la lista: los días de la semana de $dia (lunes a sábado, y
     * domingo solo si abre o tiene citas), con cuántas citas activas tiene
     * cada uno y cuántas siguen por confirmar. Una sola consulta.
     */
    private function pestanasDeLaSemana(Carbon $dia)
    {
        $lunes = $dia->copy()->startOfWeek(Carbon::MONDAY);
        $porDia = Cita::query()
            ->whereBetween('fecha_hora', [$lunes, $lunes->copy()->addDays(6)->endOfDay()])
            ->get(['fecha_hora', 'estado'])
            ->groupBy(fn (Cita $cita) => $cita->fecha_hora->toDateString());
        $horarios = HorarioAtencion::all()->keyBy('dia_semana');

        return collect(range(0, 6))
            ->map(fn (int $desplazamiento) => $lunes->copy()->addDays($desplazamiento))
            ->filter(fn (Carbon $fecha) => ! $fecha->isSunday()
                || $horarios->get(0)?->abierto
                || $porDia->has($fecha->toDateString())
                || $fecha->isSameDay($dia))
            ->map(function (Carbon $fecha) use ($dia, $porDia, $horarios) {
                $citas = $porDia->get($fecha->toDateString(), collect());

                return [
                    'fecha' => $fecha,
                    'esHoy' => $fecha->isToday(),
                    'seleccionada' => $fecha->isSameDay($dia),
                    'abierto' => (bool) $horarios->get($fecha->dayOfWeek)?->abierto,
                    'total' => $citas->reject(fn (Cita $cita) => $cita->estado->esInactiva())->count(),
                    'porConfirmar' => $citas->where('estado', EstadoCita::Agendada)->count(),
                    // Para que un día con solo canceladas no diga "0 citas" sin más (24/11/2026).
                    'canceladas' => $citas->where('estado', EstadoCita::Cancelada)->count(),
                    'noAsistio' => $citas->where('estado', EstadoCita::NoAsistio)->count(),
                ];
            })
            ->values();
    }

    /**
     * Agenda de lunes a sábado (domingo solo si abre o tiene citas).
     *
     * La semana viaja en ?semana=AAAA-MM-DD; cualquier día se normaliza a su
     * lunes y un valor inválido muestra la semana actual en vez de un error.
     */
    private function semana(Request $request): View
    {
        $lunes = $this->lunesDeLaSemana((string) $request->query('semana', ''));
        $domingo = $lunes->copy()->addDays(6);

        // Una sola consulta para toda la semana, con sus relaciones (evita N+1).
        $citasPorDia = Cita::with(['paciente', 'tratamiento', 'oferta', 'citaNueva'])
            ->whereBetween('fecha_hora', [$lunes, $domingo->copy()->endOfDay()])
            ->orderBy('fecha_hora')
            ->get()
            ->groupBy(fn (Cita $cita) => $cita->fecha_hora->toDateString());

        $horarios = HorarioAtencion::all()->keyBy('dia_semana');

        $dias = collect(range(0, 6))
            ->map(fn (int $desplazamiento) => $lunes->copy()->addDays($desplazamiento))
            ->filter(fn (Carbon $dia) => ! $dia->isSunday()
                || $horarios->get(0)?->abierto
                || $citasPorDia->has($dia->toDateString()))
            ->map(function (Carbon $dia) use ($horarios, $citasPorDia) {
                $horario = $horarios->get($dia->dayOfWeek);

                return [
                    'fecha' => $dia,
                    'esHoy' => $dia->isToday(),
                    'abierto' => (bool) $horario?->abierto,
                    'horario' => $horario?->abierto ? "{$horario->hora_apertura}:00 – {$horario->hora_cierre}:00" : null,
                    'citas' => $citasPorDia->get($dia->toDateString(), collect()),
                ];
            })
            ->values();

        return view('citas.semana', [
            'lunes' => $lunes,
            'dias' => $dias,
            'ocupacion' => app(OcupacionSemanalService::class)->calcular($dias, $horarios),
            'semanaAnterior' => $lunes->copy()->subWeek()->toDateString(),
            'semanaSiguiente' => $lunes->copy()->addWeek()->toDateString(),
        ]);
    }

    private function lunesDeLaSemana(string $valor): Carbon
    {
        $fecha = null;

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1) {
            try {
                $fecha = Carbon::createFromFormat('!Y-m-d', $valor);
                // createFromFormat acepta 2030-02-30 desbordándolo; se rechaza.
                $fecha = $fecha->format('Y-m-d') === $valor ? $fecha : null;
            } catch (\Throwable) {
                $fecha = null;
            }
        }

        return ($fecha ?? Carbon::today())->startOfWeek(Carbon::MONDAY);
    }

    public function create(): View
    {
        $servicios = Servicio::where('activo', true)
            ->with(['tratamientos' => fn ($q) => $q->where('activo', true)])
            ->orderBy('orden')
            ->get();

        // Orden alfabético, igual que el Asistente de agenda (QA en producción, 25/11/2026).
        $pacientes = Paciente::select('id', 'nombre', 'apellido')->orderBy('nombre')->orderBy('apellido')->get()->map(fn (Paciente $p) => [
            'id' => $p->id,
            'nombre' => $p->nombre_completo,
        ])->values();

        return view('citas.create', [
            'servicios' => $servicios,
            'pacientes' => $pacientes,
        ]);
    }

    public function store(Request $request, DisponibilidadService $disponibilidad, NotificacionService $notificaciones, NotificacionInternaService $notificacionesInternas, CitaReglasService $reglas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('create', Cita::class);

        $validated = $request->validate([
            'id_paciente' => ['required', 'exists:pacientes,id'],
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['nullable', 'string'],
        ]);

        $idPaciente = $validated['id_paciente'];
        [, $tratamiento] = $reglas->validarCatalogo($validated['id_servicio'], $validated['id_tratamiento']);
        $fechaHora = Carbon::parse("{$validated['fecha']} {$validated['hora']}");
        $reglas->validarFechaFutura($fechaHora);

        $cita = DB::transaction(function () use ($disponibilidad, $fechaHora, $tratamiento, $idPaciente, $validated, $auditoria, $request) {
            if (! $disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
                return null;
            }

            $cita = Cita::create([
                'id_paciente' => $idPaciente,
                'id_servicio' => $validated['id_servicio'],
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => $fechaHora,
                'duracion_estimada' => $tratamiento->duracion_estimada,
                'motivo' => $validated['motivo'] ?? null,
                'estado' => EstadoCita::Confirmada,
                'confirmada_por' => $request->user()->id,
                'fecha_confirmacion' => now(),
            ]);

            $auditoria->registrar('cita.creada', $cita, datosNuevos: $this->datosAuditoria($cita, 'interna'), request: $request);

            return $cita;
        });

        if (! $cita) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $notificaciones->citaConfirmada($cita->load(['paciente', 'tratamiento']));
        $notificacionesInternas->cambioCita($cita, 'confirmada', $request->user()->id);

        return Redirect::route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()])->with('success', 'Cita confirmada correctamente.');
    }

    public function edit(Cita $cita): View
    {
        Gate::authorize('update', $cita);

        return view('citas.edit', [
            'cita' => $cita->load(['paciente', 'servicio', 'tratamiento']),
        ]);
    }

    public function update(Request $request, Cita $cita, DisponibilidadService $disponibilidad, NotificacionService $notificaciones, NotificacionInternaService $notificacionesInternas, CitaReglasService $reglas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('update', $cita);

        if (! in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)) {
            return back()->withErrors(['estado' => 'Solo se pueden reagendar citas agendadas o confirmadas.']);
        }

        $datosAnteriores = $this->datosAuditoria($cita);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
        ]);

        $fechaHora = Carbon::parse("{$validated['fecha']} {$validated['hora']}");
        $reglas->validarFechaFutura($fechaHora);

        $reagendada = DB::transaction(function () use ($disponibilidad, $fechaHora, $cita, $datosAnteriores, $auditoria, $request) {
            if (! $disponibilidad->estaDisponible($fechaHora, $cita->tratamiento ?? $cita->duracion_estimada, $cita->id)) {
                return false;
            }

            $datosActualizados = [
                'fecha_hora' => $fechaHora,
                // Con la nueva fecha el paciente debe recibir otro recordatorio
                // y volver a confirmar su asistencia.
                'recordatorio_enviado_en' => null,
                'asistencia_confirmada_en' => null,
            ];

            // Una reserva ya confirmada conserva su confirmación al cambiar de
            // horario. Las citas antiguas que siguen agendadas mantienen su
            // flujo manual para no modificar su operación histórica.
            if ($cita->estado === EstadoCita::Agendada) {
                $datosActualizados += [
                    'estado' => EstadoCita::Agendada,
                    'confirmada_por' => null,
                    'fecha_confirmacion' => null,
                ];
            }

            $cita->update($datosActualizados);

            $auditoria->registrar('cita.reagendada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);

            return true;
        });

        if (! $reagendada) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $notificaciones->citaReagendada($cita->load('paciente'));
        $notificacionesInternas->cambioCita($cita, 'reagendada', $request->user()->id);

        return Redirect::route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()])->with('success', 'Cita reagendada correctamente.');
    }

    public function cancel(Request $request, Cita $cita, NotificacionService $notificaciones, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('cancel', $cita);

        if (! in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)) {
            return back()->withErrors(['estado' => 'Solo se pueden cancelar citas agendadas o confirmadas.']);
        }

        $datosAnteriores = $this->datosAuditoria($cita);

        $validated = $request->validate([
            'motivo_cancelacion' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($cita, $request, $validated, $datosAnteriores, $auditoria) {
            $cita->update([
                'estado' => EstadoCita::Cancelada,
                'cancelada_por' => $request->user()->id,
                'fecha_cancelacion' => now(),
                'motivo_cancelacion' => $validated['motivo_cancelacion'] ?? null,
            ]);

            $auditoria->registrar('cita.cancelada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificaciones->citaCancelada($cita->load('paciente'));
        $notificacionesInternas->cambioCita($cita, 'cancelada', $request->user()->id);

        return $this->volverALaAgenda($cita, 'Cita cancelada correctamente.');
    }

    public function confirm(Request $request, Cita $cita, NotificacionService $notificaciones, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('confirm', $cita);

        if ($cita->estado !== EstadoCita::Agendada) {
            return back()->withErrors(['estado' => 'Solo se pueden confirmar citas en estado agendada.']);
        }

        $datosAnteriores = $this->datosAuditoria($cita);

        DB::transaction(function () use ($cita, $request, $datosAnteriores, $auditoria) {
            $cita->update([
                'estado' => EstadoCita::Confirmada,
                'confirmada_por' => $request->user()->id,
                'fecha_confirmacion' => now(),
            ]);

            $auditoria->registrar('cita.confirmada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificaciones->citaConfirmada($cita->load('paciente'));
        $notificacionesInternas->cambioCita($cita, 'confirmada', $request->user()->id);

        return $this->volverALaAgenda($cita, 'Cita confirmada correctamente.');
    }

    public function start(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('start', $cita);

        if ($cita->estado !== EstadoCita::Confirmada) {
            return back()->withErrors(['estado' => 'Solo se pueden iniciar citas confirmadas.']);
        }

        // QA 24/11/2026: se podían iniciar citas de otros días, ya terminadas, y
        // varias a la vez aunque la doctora atiende a un paciente a la vez.
        if (! $cita->fecha_hora->isToday()) {
            return back()->withErrors(['estado' => 'Una cita solo se inicia el día que le toca ('
                .$cita->fecha_hora->locale('es')->translatedFormat('j \d\e F').').']);
        }
        if ($cita->yaTermino()) {
            return back()->withErrors(['estado' => 'El horario de esta cita ya terminó: márcala como «Atendida» o «No asistió».']);
        }

        $ocupada = DB::transaction(function () use ($cita, $request, $auditoria) {
            $capacidad = max(1, (int) (HorarioAtencion::where('dia_semana', $cita->fecha_hora->dayOfWeek)->value('capacidad_simultanea') ?? 1));
            $enCurso = Cita::with('paciente')
                ->where('estado', EstadoCita::EnCurso)
                ->whereDate('fecha_hora', $cita->fecha_hora->toDateString())
                ->whereKeyNot($cita->getKey())
                ->orderBy('fecha_hora')
                ->lockForUpdate()
                ->get();

            if ($enCurso->count() >= $capacidad) {
                return $enCurso->first();
            }

            $datosAnteriores = $this->datosAuditoria($cita);
            $cita->update(['estado' => EstadoCita::EnCurso]);
            $auditoria->registrar('cita.iniciada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);

            return null;
        });

        if ($ocupada) {
            $quien = trim(($ocupada->paciente?->nombre ?? '').' '.($ocupada->paciente?->apellido ?? '')) ?: 'otro paciente';

            return back()->withErrors(['estado' => "Primero completa la cita de {$quien} ({$ocupada->fecha_hora->format('H:i')}), que sigue en curso."]);
        }
        $notificacionesInternas->cambioCita($cita, 'iniciada', $request->user()->id);

        return $this->volverALaAgenda($cita, 'Cita iniciada correctamente.');
    }

    public function noShow(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('noShow', $cita);

        // QA 24/11/2026: también una agendada que nunca se confirmó y ya pasó.
        if (! in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)) {
            return back()->withErrors(['estado' => 'Solo se pueden marcar como no asistidas las citas agendadas o confirmadas.']);
        }

        if ($cita->fecha_hora->isFuture()) {
            return back()->withErrors(['estado' => 'No se puede marcar una cita como no asistida antes de su fecha y hora programadas.']);
        }

        DB::transaction(function () use ($cita, $request, $auditoria) {
            $datosAnteriores = $this->datosAuditoria($cita);
            $cita->update(['estado' => EstadoCita::NoAsistio]);
            $auditoria->registrar('cita.no_asistio', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificacionesInternas->cambioCita($cita, 'no_asistio', $request->user()->id);

        return $this->volverALaAgenda($cita, 'Cita marcada como no asistida.');
    }

    public function complete(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('complete', $cita);

        // QA 24/11/2026: si nadie presionó «Iniciar», una cita cuya hora ya llegó
        // se marca «Atendida» en un paso (antes había que iniciarla y completarla).
        $atendidaDirecto = in_array($cita->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)
            && ! $cita->fecha_hora->isFuture();

        if ($cita->estado !== EstadoCita::EnCurso && ! $atendidaDirecto) {
            return back()->withErrors(['estado' => $cita->fecha_hora->isFuture()
                ? 'Una cita solo se marca como atendida cuando llega su hora.'
                : 'Solo se pueden completar citas en curso.']);
        }

        DB::transaction(function () use ($cita, $request, $auditoria) {
            $datosAnteriores = $this->datosAuditoria($cita);
            $cita->update(['estado' => EstadoCita::Completada]);
            $auditoria->registrar('cita.completada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificacionesInternas->cambioCita($cita, 'completada', $request->user()->id);

        return $this->volverALaAgenda($cita, $atendidaDirecto ? 'Cita marcada como atendida.' : 'Cita completada correctamente.');
    }

    /** @return array<string, string> */
    /**
     * Tras cambiar el estado de una cita se vuelve a la misma agenda (día, vista y
     * filtros) desde la que se pulsó el botón; si no se vino de la agenda, al día
     * de la cita (24/11/2026: antes siempre regresaba a hoy).
     */
    private function volverALaAgenda(Cita $cita, string $mensaje): RedirectResponse
    {
        $agenda = route('citas.index');
        $anterior = url()->previous();
        $vieneDeLaAgenda = $anterior === $agenda || str_starts_with($anterior, $agenda.'?');

        return Redirect::to($vieneDeLaAgenda ? $anterior : route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]))
            ->with('success', $mensaje);
    }

    private function datosAuditoria(Cita $cita, ?string $canal = null): array
    {
        $datos = [
            // El modelo recién creado aún no trae los defaults de la base;
            // el flujo vigente crea las reservas nuevas ya confirmadas.
            'estado' => $cita->estado?->value ?? EstadoCita::Confirmada->value,
            'fecha_hora' => $cita->fecha_hora->toIso8601String(),
        ];

        if ($canal !== null) {
            $datos['canal'] = $canal;
        }

        return $datos;
    }
}
