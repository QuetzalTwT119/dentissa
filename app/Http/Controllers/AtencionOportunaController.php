<?php

namespace App\Http\Controllers;

use App\Models\ListaEspera;
use App\Models\Servicio;
use App\Models\SolicitudUrgencia;
use App\Services\CitaReglasService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AtencionOportunaController extends Controller
{
    public function urgencia(): View
    {
        return view('atencion.urgencia');
    }

    public function guardarUrgencia(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'motivo' => ['required', 'string', 'min:10', 'max:1500'],
            'senales_reportadas' => ['nullable', 'array'],
            'senales_reportadas.*' => ['string', 'in:dolor,inflamacion,sangrado,traumatismo,otro'],
        ]);

        SolicitudUrgencia::create($validated + ['estado' => 'pendiente']);

        return redirect()->route('atencion.urgencia')
            ->with('success', 'Recibimos tu solicitud. El consultorio la revisará y se comunicará contigo.');
    }

    public function listaEspera(): View
    {
        $servicios = Servicio::where('activo', true)
            ->with(['tratamientos' => fn ($query) => $query->where('activo', true)])
            ->orderBy('orden')
            ->get();

        return view('atencion.lista-espera', compact('servicios'));
    }

    public function guardarListaEspera(Request $request, CitaReglasService $reglas): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'id_servicio' => ['nullable', Rule::exists('servicios', 'id')->where('activo', true)],
            'id_tratamiento' => ['nullable', Rule::exists('tratamientos', 'id')->where('activo', true)],
            'fecha_preferida' => ['nullable', 'date', 'after_or_equal:today'],
            'franja_horaria' => ['required', 'in:cualquiera,manana,tarde'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        $idServicio = $validated['id_servicio'] ?? null;
        $idTratamiento = $validated['id_tratamiento'] ?? null;

        if ($idTratamiento && ! $idServicio) {
            throw ValidationException::withMessages([
                'id_servicio' => 'Selecciona el servicio al que pertenece el tratamiento.',
            ]);
        }

        if ($idServicio && $idTratamiento) {
            $reglas->validarCatalogo($idServicio, $idTratamiento);
        }

        ListaEspera::create($validated + ['estado' => 'pendiente']);

        return redirect()->route('atencion.lista-espera')
            ->with('success', 'Te agregamos a la lista de espera. Te contactaremos solo si aparece un horario que coincida.');
    }
}
