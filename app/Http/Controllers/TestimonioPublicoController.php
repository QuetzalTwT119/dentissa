<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Services\NotificacionInternaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * "Comparte tu experiencia" (portada): el paciente escribe su testimonio,
 * entra como PENDIENTE y el personal lo aprueba en el panel antes de publicarse.
 *
 * Privacidad: la respuesta es siempre la misma, exista o no el paciente, para
 * que el formulario no sirva para averiguar quién atiende la clínica.
 */
class TestimonioPublicoController extends Controller
{
    private const GRACIAS = '¡Gracias por compartir tu experiencia! La clínica la revisará antes de publicarla.';

    public function store(Request $request, NotificacionInternaService $avisos): RedirectResponse
    {
        // Campo trampa: invisible para personas; los robots lo llenan.
        if (filled($request->input('sitio_web'))) {
            return $this->gracias();
        }

        $validador = Validator::make($request->all(), [
            'contacto' => ['required', 'string', 'max:120'],
            'calificacion' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['required', 'string', 'min:15', 'max:600'],
            'autorizacion' => ['accepted'],
        ], [
            'contacto.required' => 'Escribe el teléfono o correo con el que agendaste.',
            'calificacion.required' => 'Elige de 1 a 5 estrellas.',
            'comentario.required' => 'Cuéntanos tu experiencia.',
            'comentario.min' => 'Escribe al menos 15 caracteres.',
            'autorizacion.accepted' => 'Necesitamos tu autorización para publicarlo.',
        ]);

        // Con error se regresa a la sección (no al inicio de la página) con lo ya escrito.
        if ($validador->fails()) {
            return redirect(url('/').'#testimonios')->withErrors($validador, 'testimonio')->withInput();
        }

        $datos = $validador->validated();

        $paciente = $this->buscarPaciente($datos['contacto']);

        if (! $paciente) {
            Log::info('Testimonio público descartado: contacto sin paciente registrado.');

            return $this->gracias();
        }

        $testimonio = Testimonio::create([
            'id_paciente' => $paciente->id,
            'id_cita' => Cita::where('id_paciente', $paciente->id)->latest('fecha_hora')->value('id'),
            'calificacion' => $datos['calificacion'],
            'comentario' => trim($datos['comentario']),
            'estado' => 'pendiente',
        ]);

        $avisos->alertaSistema(
            'testimonio_nuevo',
            'Nuevo testimonio por revisar',
            "{$paciente->nombre} dejó un testimonio de {$testimonio->calificacion} estrellas. Apruébalo en Contenido → Testimonios para publicarlo.",
        );

        return $this->gracias();
    }

    /** Por correo exacto o por los últimos 10 dígitos del teléfono (ignora espacios, guiones y +52). */
    private function buscarPaciente(string $contacto): ?Paciente
    {
        $contacto = trim($contacto);

        if (str_contains($contacto, '@')) {
            return Paciente::whereRaw('LOWER(email) = ?', [mb_strtolower($contacto)])->first();
        }

        $digitos = substr(preg_replace('/\D/', '', $contacto), -10);
        if (strlen($digitos) < 10) {
            return null;
        }

        $limpio = "REPLACE(REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '+', ''), '.', '')";

        return Paciente::where(DB::raw($limpio), 'like', '%'.$digitos)->first();
    }

    private function gracias(): RedirectResponse
    {
        return redirect(url('/').'#testimonios')->with('testimonio_enviado', self::GRACIAS);
    }
}
