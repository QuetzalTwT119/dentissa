<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCita;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
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

    public function store(Request $request, DisponibilidadService $disponibilidad, NotificacionService $notificaciones): RedirectResponse
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
        $tratamiento = Tratamiento::findOrFail($validated['id_tratamiento']);
        $fechaHora = Carbon::parse("{$validated['fecha']} {$validated['hora']}");

        $cita = DB::transaction(function () use ($disponibilidad, $fechaHora, $tratamiento, $idPaciente, $validated) {
            if (! $disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
                return null;
            }

            return Cita::create([
                'id_paciente' => $idPaciente,
                'id_servicio' => $validated['id_servicio'],
                'id_tratamiento' => $tratamiento->id,
                'fecha_hora' => $fechaHora,
                'duracion_estimada' => $tratamiento->duracion_estimada,
                'motivo' => $validated['motivo'] ?? null,
            ]);
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

    public function update(Request $request, Cita $cita, DisponibilidadService $disponibilidad, NotificacionService $notificaciones): RedirectResponse
    {
        Gate::authorize('update', $cita);

        $validated = $request->validate([
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
        ]);

        $fechaHora = Carbon::parse("{$validated['fecha']} {$validated['hora']}");

        $reagendada = DB::transaction(function () use ($disponibilidad, $fechaHora, $cita) {
            if (! $disponibilidad->estaDisponible($fechaHora, $cita->tratamiento ?? $cita->duracion_estimada, $cita->id)) {
                return false;
            }

            $cita->update([
                'fecha_hora' => $fechaHora,
                'estado' => EstadoCita::Agendada,
                'confirmada_por' => null,
                'fecha_confirmacion' => null,
            ]);

            return true;
        });

        if (! $reagendada) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        $notificaciones->citaReagendada($cita->load('paciente'));

        return Redirect::route('citas.index');
    }

    public function cancel(Request $request, Cita $cita, NotificacionService $notificaciones): RedirectResponse
    {
        Gate::authorize('cancel', $cita);

        $validated = $request->validate([
            'motivo_cancelacion' => ['nullable', 'string'],
        ]);

        $cita->update([
            'estado' => EstadoCita::Cancelada,
            'cancelada_por' => $request->user()->id,
            'fecha_cancelacion' => now(),
            'motivo_cancelacion' => $validated['motivo_cancelacion'] ?? null,
        ]);

        $notificaciones->citaCancelada($cita->load('paciente'));

        return Redirect::route('citas.index');
    }

    public function confirm(Request $request, Cita $cita, NotificacionService $notificaciones): RedirectResponse
    {
        Gate::authorize('confirm', $cita);

        if ($cita->estado !== EstadoCita::Agendada) {
            return back()->withErrors(['estado' => 'Solo se pueden confirmar citas en estado agendada.']);
        }

        $cita->update([
            'estado' => EstadoCita::Confirmada,
            'confirmada_por' => $request->user()->id,
            'fecha_confirmacion' => now(),
        ]);

        $notificaciones->citaConfirmada($cita->load('paciente'));

        return Redirect::route('citas.index');
    }

    public function complete(Cita $cita): RedirectResponse
    {
        Gate::authorize('complete', $cita);

        $cita->update(['estado' => EstadoCita::Completada]);

        return Redirect::route('citas.index');
    }
}
