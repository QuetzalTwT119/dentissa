<?php

namespace App\Http\Controllers;

use App\Models\ListaEspera;
use App\Models\Servicio;
use App\Models\SolicitudUrgencia;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function guardarListaEspera(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'id_servicio' => ['nullable', 'exists:servicios,id'],
            'id_tratamiento' => ['nullable', 'exists:tratamientos,id'],
            'fecha_preferida' => ['nullable', 'date', 'after_or_equal:today'],
            'franja_horaria' => ['required', 'in:cualquiera,manana,tarde'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        ListaEspera::create($validated + ['estado' => 'pendiente']);

        return redirect()->route('atencion.lista-espera')
            ->with('success', 'Te agregamos a la lista de espera. Te contactaremos solo si aparece un horario que coincida.');
    }
}
