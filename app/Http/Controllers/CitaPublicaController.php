<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Services\CrearCitaService;
use App\Services\NotificacionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, CrearCitaService $crearCita, NotificacionService $notificaciones): RedirectResponse
    {
        $validated = $request->validate([
            'id_servicio' => ['required', 'exists:servicios,id'],
            'id_tratamiento' => ['required', 'exists:tratamientos,id'],
            'fecha' => ['required', 'date'],
            'hora' => ['required', 'date_format:H:i'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => [
                'required',
                'string',
                'size:10',
                'regex:/^[0-9]{10}$/',
            ],
            'email' => ['required', 'email', 'max:100'],
            'motivo' => ['nullable', 'string'],
        ], [
            'telefono.regex' => 'Ingresa exactamente 10 dígitos numéricos de México.',
        ]);

        $resultado = $crearCita->crear($validated, 'publico', request: $request);
        $cita = $resultado['cita'];

        if (! $cita) {
            return back()->withErrors(['hora' => 'Ese horario ya no está disponible. Por favor elige otro.']);
        }

        if ($resultado['creada']) {
            $notificaciones->citaConfirmada($cita->load(['paciente', 'tratamiento']));
        }

        return Redirect::route('citas.publica.create')
            ->with('success', 'Tu cita quedó confirmada. Te esperamos.');
    }
}
