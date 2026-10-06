<?php

namespace App\Services;

use Illuminate\Support\Str;

class AtencionOportunaService
{
    /**
     * Reconoce solicitudes administrativas y devuelve una orientación segura.
     * No clasifica ni diagnostica condiciones clínicas.
     *
     * @return array{respuesta: string, accion: array{etiqueta: string, url: string}}|null
     */
    public function responder(string $texto): ?array
    {
        $normalizado = trim(Str::ascii(Str::lower($texto)));

        if (Str::contains($normalizado, [
            'urgencia', 'emergencia dental', 'dolor intenso', 'sangrado', 'inflamacion', 'traumatismo', 'golpe dental',
        ])) {
            return [
                'respuesta' => 'Puedo ayudarte a enviar una solicitud para que el consultorio revise tu caso y te contacte. Este asistente no da diagnósticos. Si existe peligro inmediato para la vida, llama a emergencias.',
                'accion' => [
                    'etiqueta' => 'Solicitar atención urgente',
                    'url' => route('atencion.urgencia'),
                ],
            ];
        }

        if (Str::contains($normalizado, [
            'lista de espera', 'no hay horario', 'no encuentro horario', 'no hay citas', 'todo ocupado', 'no hay disponibilidad',
        ])) {
            return [
                'respuesta' => 'Puedes unirte a la lista de espera. El consultorio te contactará solamente si se libera un horario compatible; esto no confirma una cita.',
                'accion' => [
                    'etiqueta' => 'Unirme a la lista de espera',
                    'url' => route('atencion.lista-espera'),
                ],
            ];
        }

        return null;
    }
}
