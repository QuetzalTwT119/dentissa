<?php

namespace App\Http\Controllers;

use App\Mail\RecordatorioCita;
use App\Models\Cita;
use App\Services\RespuestaRecordatorioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Páginas públicas a las que llevan los botones del correo de recordatorio.
 *
 * GET solo muestra la pregunta; el cambio ocurre con POST. Muchos correos
 * (Gmail, Outlook, antivirus) abren los enlaces por su cuenta para revisarlos:
 * si el GET cancelara, una cita podría cancelarse sin que el paciente pulse
 * nada. Ambas rutas exigen la firma, así que nadie puede cambiar el número de
 * cita ni la acción en la URL.
 */
class RespuestaRecordatorioController extends Controller
{
    public function show(Request $request, Cita $cita, string $accion, RespuestaRecordatorioService $respuestas): View
    {
        return view('citas-publicas.recordatorio', [
            'cita' => $cita->load('tratamiento'),
            'accion' => $accion,
            'disponible' => $respuestas->admiteRespuesta($cita),
            'urlFormulario' => RecordatorioCita::urlFirmada('citas.recordatorio.responder', $cita, $accion),
            'resultado' => $request->session()->get('resultado_recordatorio'),
        ]);
    }

    public function responder(Cita $cita, string $accion, RespuestaRecordatorioService $respuestas): RedirectResponse
    {
        $resultado = $respuestas->responder($cita, $accion);

        // Post/Redirect/Get: recargar la página de resultado no reenvía el
        // formulario. El GET firmado se abre ahora con el resultado en sesión.
        return redirect()
            ->to(RecordatorioCita::urlFirmada('citas.recordatorio.show', $cita, $accion))
            ->with('resultado_recordatorio', $resultado);
    }
}
