<?php

namespace App\Services;

use App\Models\PreguntaFrecuente;
use Illuminate\Support\Str;

class AsistentePacienteService
{
    /**
     * Responde únicamente con información ya aprobada en preguntas frecuentes.
     * No genera orientación clínica ni inventa información para pacientes.
     */
    public function responder(string $consulta): array
    {
        $palabrasConsulta = $this->palabras($consulta);

        $mejorCoincidencia = PreguntaFrecuente::query()
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function (PreguntaFrecuente $pregunta) use ($palabrasConsulta) {
                $coincidencias = count(array_intersect($palabrasConsulta, $this->palabras($pregunta->pregunta)));

                return compact('pregunta', 'coincidencias');
            })
            ->sortByDesc('coincidencias')
            ->first();

        if (! $mejorCoincidencia || $mejorCoincidencia['coincidencias'] === 0) {
            return [
                'encontrada' => false,
                'respuesta' => 'No encontré una respuesta aprobada para esa duda. Por favor comunícate con el consultorio para orientarte.',
            ];
        }

        /** @var PreguntaFrecuente $pregunta */
        $pregunta = $mejorCoincidencia['pregunta'];

        return [
            'encontrada' => true,
            'pregunta' => $pregunta->pregunta,
            'respuesta' => $pregunta->respuesta,
        ];
    }

    /** @return array<int, string> */
    private function palabras(string $texto): array
    {
        $comunes = ['a', 'al', 'con', 'cuanto', 'de', 'del', 'el', 'en', 'es', 'la', 'las', 'lo', 'los', 'me', 'mi', 'para', 'por', 'que', 'quiero', 'se', 'saber', 'su', 'una', 'un', 'y'];

        return collect(preg_split('/[^a-z0-9]+/', Str::ascii(Str::lower($texto))))
            ->filter(fn (?string $palabra) => $palabra && strlen($palabra) >= 3 && ! in_array($palabra, $comunes, true))
            ->unique()
            ->values()
            ->all();
    }
}
