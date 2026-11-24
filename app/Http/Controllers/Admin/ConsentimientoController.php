<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consentimiento;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Services\AuditoriaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Redirect;

/**
 * Consentimiento informado (24/11/2026): la doctora escribe qué procedimiento,
 * beneficios, riesgos y alternativas explicó; se guarda en el expediente y se
 * imprime para que el paciente (o su representante), la doctora y dos testigos
 * lo firmen a mano. Sin firma digital a propósito.
 */
class ConsentimientoController extends Controller
{
    public function create(Paciente $paciente): View
    {
        return view('admin.consentimientos.create', [
            'paciente' => $paciente,
            'tratamientos' => Tratamiento::where('activo', true)->orderBy('nombre')->pluck('nombre'),
        ]);
    }

    public function store(Request $request, Paciente $paciente, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'procedimiento' => ['required', 'string', 'max:150'],
            'dientes' => ['nullable', 'string', 'max:100'],
            'beneficios' => ['required', 'string', 'max:2000'],
            'riesgos' => ['required', 'string', 'max:2000'],
            'alternativas' => ['required', 'string', 'max:2000'],
        ], [
            'procedimiento.required' => 'Escribe el procedimiento que se va a realizar.',
        ]);

        $consentimiento = Consentimiento::create($validated + [
            'id_paciente' => $paciente->id,
            'id_doctor' => $request->user()->id,
        ]);

        // Sin el contenido clínico en la bitácora: solo qué se generó.
        $auditoria->registrar('consentimiento.creado', $consentimiento, datosNuevos: ['id_paciente' => $paciente->id], request: $request);

        return Redirect::route('admin.pacientes.historial.index', $paciente)
            ->with('success', 'Consentimiento guardado. Imprímelo para que lo firmen.')
            ->with('imprimir_consentimiento', route('admin.pacientes.consentimientos.pdf', [$paciente, $consentimiento]));
    }

    public function pdf(Paciente $paciente, Consentimiento $consentimiento): HttpResponse
    {
        abort_unless($consentimiento->id_paciente === $paciente->id, 404);

        $historia = $paciente->historiaClinica;
        $alergias = $historia ? HistoriaClinica::nombres($historia->alergias, HistoriaClinica::ALERGIAS) : [];
        if ($historia && filled($historia->alergias_otras)) {
            $alergias[] = trim($historia->alergias_otras);
        }

        return Pdf::loadView('pdf.consentimiento', [
            'paciente' => $paciente,
            'consentimiento' => $consentimiento->load('doctor'),
            'edad' => $historia?->edad(),
            'alergias' => implode(', ', $alergias),
        ])->setPaper('letter')->stream("consentimiento-{$paciente->id}-{$consentimiento->id}.pdf");
    }
}
