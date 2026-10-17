<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Services\CitaReglasService;
use App\Services\DisponibilidadService;
use App\Services\NotificacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class CitaPublicaController extends Controller
{
    public function create(): View
    {
        $servicios = Servicio::where('activo', true)
            ->with(['tratamientos' => fn ($q) => $q->where('activo', true)])
            ->orderBy('orden')
            ->get();

        return view('citas-publicas.create', [
            'servicios' => $servicios,
        ]);
    }

    public function store(Request $request, DisponibilidadService $disponibilidad, NotificacionService $notificaciones, CitaReglasService $reglas): RedirectResponse
    {
        $validated = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'size:10', 'regex:/^[0-9]{10}$/'],
            'email' => ['required', 'email', 'max:100'],
            'motivo' => ['nullable', 'string'],
        ], [
            'telefono.regex' => 'Ingresa exactamente 10 dígitos numéricos de México.',
        ]);

        [, $tratamiento] = $reglas->validarCatalogo($validated['id_servicio'], $validated['id_tratamiento']);
        $fechaHora = Carbon::parse("{$validated['fecha']} {$validated['hora']}");
        $reglas->validarFechaFutura($fechaHora);

        // La notificación (WhatsApp/correo) se dispara DESPUÉS de que la
        // transacción confirma — nunca dentro de ella, para no mantener el
        // lock de disponibilidad abierto mientras se espera a una API externa.
        $cita = DB::transaction(function () use ($disponibilidad, $fechaHora, $tratamiento, $validated) {
            if (! $disponibilidad->estaDisponible($fechaHora, $tratamiento)) {
                return null;
            }

            $paciente = Paciente::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'nombre' => $validated['nombre'],
                    'apellido' => $validated['apellido'],
                    'telefono' => $validated['telefono'],
                ],
            );

            return Cita::create([
                'id_paciente' => $paciente->id,
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

        return Redirect::route('citas.publica.create')
            ->with('success', 'Tu cita quedó agendada. Te esperamos.');
    }
}
