<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Services\AuditoriaService;
use App\Services\CitaReglasService;
use App\Services\DisponibilidadService;
use App\Services\NotificacionService;
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
        $validated = $request->validate([
            'fecha' => ['nullable', 'date'],
            'estado' => ['nullable', Rule::enum(EstadoCita::class)],
        ]);

        $query = Cita::with(['paciente', 'servicio', 'tratamiento'])
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha_hora', $validated['fecha']))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $validated['estado']));

        return view('citas.index', [
            'citas' => $query->orderByDesc('fecha_hora')->get(),
            'filtros' => $request->only(['fecha', 'estado']),
        ]);
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

    public function store(Request $request, DisponibilidadService $disponibilidad, NotificacionService $notificaciones, CitaReglasService $reglas, AuditoriaService $auditoria): RedirectResponse
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
            ]);

            $auditoria->registrar('cita.creada', $cita, datosNuevos: $this->datosAuditoria($cita, 'interna'), request: $request);

            return $cita;
        });

        if (! $cita) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $notificaciones->citaAgendada($cita->load(['paciente', 'tratamiento']));

        return Redirect::route('citas.index');
    }

    public function edit(Cita $cita): View
    {
        Gate::authorize('update', $cita);

        return view('citas.edit', [
            'cita' => $cita->load(['paciente', 'servicio', 'tratamiento']),
        ]);
    }

    public function update(Request $request, Cita $cita, DisponibilidadService $disponibilidad, NotificacionService $notificaciones, CitaReglasService $reglas, AuditoriaService $auditoria): RedirectResponse
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

            $cita->update([
                'fecha_hora' => $fechaHora,
                'estado' => EstadoCita::Agendada,
                'confirmada_por' => null,
                'fecha_confirmacion' => null,
            ]);

            $auditoria->registrar('cita.reagendada', $cita, $datosAnteriores, $this->datosAuditoria($cita), $request);

            return true;
        });

        if (! $reagendada) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $notificaciones->citaReagendada($cita->load('paciente'));

        return Redirect::route('citas.index');
    }

    public function cancel(Request $request, Cita $cita, NotificacionService $notificaciones, AuditoriaService $auditoria): RedirectResponse
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

        return Redirect::route('citas.index');
    }

    public function confirm(Request $request, Cita $cita, NotificacionService $notificaciones, AuditoriaService $auditoria): RedirectResponse
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

        return Redirect::route('citas.index');
    }

    public function start(Request $request, Cita $cita, AuditoriaService $auditoria): RedirectResponse
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

        return Redirect::route('citas.index');
    }

    public function noShow(Request $request, Cita $cita, AuditoriaService $auditoria): RedirectResponse
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

        return Redirect::route('citas.index');
    }

    public function complete(Request $request, Cita $cita, AuditoriaService $auditoria): RedirectResponse
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

        return Redirect::route('citas.index');
    }

    /** @return array<string, string> */
    private function datosAuditoria(Cita $cita, ?string $canal = null): array
    {
        $datos = [
            // El modelo recién creado aún no trae los defaults aplicados por
            // la base de datos; una cita nueva nace en estado agendada.
            'estado' => $cita->estado?->value ?? EstadoCita::Agendada->value,
            'fecha_hora' => $cita->fecha_hora->toIso8601String(),
        ];

        if ($canal !== null) {
            $datos['canal'] = $canal;
        }

        return $datos;
    }
}
