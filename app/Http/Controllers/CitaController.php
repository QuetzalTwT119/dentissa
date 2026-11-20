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
    public function index(Request $request): View
    {
        if ($request->query('vista') === 'semana') {
            return $this->semana($request);
        }

        $validated = $request->validate([
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::enum(EstadoCita::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);

        $terminos = preg_split('/\s+/', trim($validated['buscar'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $query = Cita::with(['paciente', 'servicio', 'tratamiento'])
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha_hora', $validated['fecha']))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $validated['estado']))
            ->when($terminos !== [], function ($q) use ($terminos) {
                foreach ($terminos as $termino) {
                    $coincidencia = '%'.$termino.'%';

                    $q->where(function ($buscar) use ($coincidencia) {
                        $buscar->whereHas('paciente', fn ($paciente) => $paciente
                            ->where('nombre', 'like', $coincidencia)
                            ->orWhere('apellido', 'like', $coincidencia)
                            ->orWhere('telefono', 'like', $coincidencia)
                            ->orWhere('email', 'like', $coincidencia))
                            ->orWhereHas('servicio', fn ($servicio) => $servicio->where('nombre', 'like', $coincidencia))
                            ->orWhereHas('tratamiento', fn ($tratamiento) => $tratamiento->where('nombre', 'like', $coincidencia));
                    });
                }
            });

        return view('citas.index', [
            'citas' => $query->orderByDesc('fecha_hora')->paginate($this->paginationPageSize($request))->withQueryString(),
            'filtros' => $request->only(['fecha', 'estado', 'buscar']),
        ]);
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
        $citasPorDia = Cita::with(['paciente', 'tratamiento'])
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

        $pacientes = Paciente::select('id', 'nombre', 'apellido')->get()->map(fn (Paciente $p) => [
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

        return Redirect::route('citas.index')->with('success', 'Cita confirmada correctamente.');
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

        return Redirect::route('citas.index')->with('success', 'Cita reagendada correctamente.');
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

        return Redirect::route('citas.index')->with('success', 'Cita cancelada correctamente.');
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

        return Redirect::route('citas.index')->with('success', 'Cita confirmada correctamente.');
    }

    public function start(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('start', $cita);

        if ($cita->estado !== EstadoCita::Confirmada) {
            return back()->withErrors(['estado' => 'Solo se pueden iniciar citas confirmadas.']);
        }

        DB::transaction(function () use ($cita, $request, $auditoria) {
            $datosAnteriores = $this->datosAuditoria($cita);
            $cita->update(['estado' => EstadoCita::EnCurso]);
            $auditoria->registrar('cita.iniciada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificacionesInternas->cambioCita($cita, 'iniciada', $request->user()->id);

        return Redirect::route('citas.index')->with('success', 'Cita iniciada correctamente.');
    }

    public function noShow(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('noShow', $cita);

        if ($cita->estado !== EstadoCita::Confirmada) {
            return back()->withErrors(['estado' => 'Solo se pueden marcar como no asistidas las citas confirmadas.']);
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

        return Redirect::route('citas.index')->with('success', 'Cita marcada como no asistida.');
    }

    public function complete(Request $request, Cita $cita, NotificacionInternaService $notificacionesInternas, AuditoriaService $auditoria): RedirectResponse
    {
        Gate::authorize('complete', $cita);

        if ($cita->estado !== EstadoCita::EnCurso) {
            return back()->withErrors(['estado' => 'Solo se pueden completar citas en curso.']);
        }

        DB::transaction(function () use ($cita, $request, $auditoria) {
            $datosAnteriores = $this->datosAuditoria($cita);
            $cita->update(['estado' => EstadoCita::Completada]);
            $auditoria->registrar('cita.completada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);
        });
        $notificacionesInternas->cambioCita($cita, 'completada', $request->user()->id);

        return Redirect::route('citas.index')->with('success', 'Cita completada correctamente.');
    }

    /** @return array<string, string> */
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
