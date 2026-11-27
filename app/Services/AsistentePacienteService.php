<?php

namespace App\Services;

use App\Models\PreguntaFrecuente;
use Illuminate\Support\Str;

class AsistentePacienteService
{
    private const PUNTAJE_MINIMO = 0.62;

    private const MARGEN_AMBIGUEDAD = 0.12;

    /**
     * Responde únicamente con información ya aprobada en preguntas frecuentes.
     * No genera orientación clínica ni inventa información para pacientes.
     * Si la intención no es clara, prefiere pedir que reformulen la duda.
     *
     * @return array{encontrada: bool, respuesta: string, pregunta?: string, ambigua?: bool}
     */
    public function responder(string $consulta): array
    {
        $palabrasConsulta = $this->palabras($consulta);

        // Una sola palabra no da contexto suficiente para distinguir una duda.
        if (count($palabrasConsulta) < 2) {
            return $this->respuestaSinCoincidencia();
        }

        $coincidencias = PreguntaFrecuente::query()
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function (PreguntaFrecuente $pregunta) use ($palabrasConsulta) {
                $frases = array_merge(
                    [$pregunta->pregunta],
                    preg_split('/\R/u', (string) $pregunta->variantes, -1, PREG_SPLIT_NO_EMPTY) ?: [],
                );

                $puntaje = collect($frases)
                    ->map(fn (string $frase) => $this->puntaje($palabrasConsulta, $this->palabras($frase)))
                    ->max() ?? 0.0;

                return compact('pregunta', 'puntaje');
            })
            ->filter(fn (array $coincidencia) => $coincidencia['puntaje'] > 0)
            ->sortByDesc('puntaje')
            ->values();

        $mejorCoincidencia = $coincidencias->first();
        $segundaCoincidencia = $coincidencias->get(1);

        if (! $mejorCoincidencia || $mejorCoincidencia['puntaje'] < self::PUNTAJE_MINIMO) {
            return $this->respuestaSinCoincidencia();
        }

        // Si dos FAQs son casi igual de probables, no se elige una al azar.
        if ($segundaCoincidencia
            && $segundaCoincidencia['puntaje'] >= self::PUNTAJE_MINIMO - self::MARGEN_AMBIGUEDAD
            && ($mejorCoincidencia['puntaje'] - $segundaCoincidencia['puntaje']) < self::MARGEN_AMBIGUEDAD) {
            return $this->respuestaSinCoincidencia(ambigua: true);
        }

        /** @var PreguntaFrecuente $pregunta */
        $pregunta = $mejorCoincidencia['pregunta'];

        return [
            'encontrada' => true,
            'pregunta' => $pregunta->pregunta,
            'respuesta' => $pregunta->respuesta,
        ];
    }

    /**
     * 27/11/2026: "me duele una muela", "se me hinchó", "me golpeé un diente"… no
     * coincidían con ninguna pregunta y el paciente recibía "No pude identificar".
     * Ahora van a la pregunta de urgencias aprobada (o, si no existe, a llamar).
     * No da orientación clínica: solo dice cómo contactar al consultorio.
     *
     * @return array{encontrada: true, respuesta: string, pregunta: string}|null
     */
    public function respuestaUrgencia(string $consulta): ?array
    {
        $texto = Str::ascii(Str::lower($consulta));

        if (! Str::contains($texto, ['duele', 'dolor', 'hinch', 'inflam', 'sangr', 'golpe', 'urgencia', 'emergencia', 'se me rompio', 'se me cayo', 'absceso', 'pus'])) {
            return null;
        }

        $urgencias = PreguntaFrecuente::query()
            ->where('activa', true)
            ->where('pregunta', 'like', '%rgencia%')
            ->orderBy('orden')
            ->first();

        return [
            'encontrada' => true,
            'pregunta' => $urgencias?->pregunta ?? '¿Atienden urgencias?',
            'respuesta' => $urgencias?->respuesta
                ?? 'Si tienes dolor fuerte, inflamación o un golpe en un diente, llama o escribe al consultorio al '.config('dentissa.contacto.telefono').' para darte un espacio lo antes posible.',
        ];
    }

    /** @return array{encontrada: false, respuesta: string, ambigua?: bool} */
    private function respuestaSinCoincidencia(bool $ambigua = false): array
    {
        return array_filter([
            'encontrada' => false,
            'respuesta' => 'No pude identificar con seguridad una respuesta aprobada. Intenta escribir la duda con otras palabras o comunícate con el consultorio para orientarte.',
            'ambigua' => $ambigua ? true : null,
        ], fn ($valor) => $valor !== null);
    }

    /**
     * Puntaje Dice entre palabras de la consulta y una pregunta/variante.
     * Se requiere más de una palabra compartida para evitar respuestas por
     * coincidencias accidentales. Las palabras de consulta solo se cuentan una vez.
     *
     * @param  array<int, string>  $consulta
     * @param  array<int, string>  $frase
     */
    private function puntaje(array $consulta, array $frase): float
    {
        if (count($consulta) < 2 || count($frase) === 0) {
            return 0.0;
        }

        $disponibles = $frase;
        $coincidencias = 0;

        foreach ($consulta as $palabraConsulta) {
            foreach ($disponibles as $indice => $palabraFrase) {
                if (! $this->palabrasParecidas($palabraConsulta, $palabraFrase)) {
                    continue;
                }

                unset($disponibles[$indice]);
                $coincidencias++;
                break;
            }
        }

        if ($coincidencias < 2) {
            return 0.0;
        }

        return (2 * $coincidencias) / (count($consulta) + count($frase));
    }

    private function palabrasParecidas(string $una, string $otra): bool
    {
        if ($una === $otra) {
            return true;
        }

        // No aplicar distancia de edición a palabras cortas: aumenta falsos positivos.
        if (min(strlen($una), strlen($otra)) < 5) {
            return false;
        }

        $distanciaMaxima = max(strlen($una), strlen($otra)) >= 9 ? 2 : 1;

        return abs(strlen($una) - strlen($otra)) <= $distanciaMaxima
            && levenshtein($una, $otra) <= $distanciaMaxima;
    }

    /** @return array<int, string> */
    private function palabras(string $texto): array
    {
        $comunes = ['a', 'al', 'algo', 'con', 'como', 'cual', 'cuales', 'cuando', 'cuanto', 'de', 'decir', 'del', 'dices', 'dime', 'donde', 'el', 'en', 'es', 'esta', 'este', 'esto', 'hay', 'la', 'las', 'lo', 'los', 'me', 'mi', 'para', 'por', 'puede', 'pueden', 'puedo', 'que', 'quiero', 'saber', 'se', 'ser', 'su', 'tiene', 'tienen', 'un', 'una', 'y'];

        return collect(preg_split('/[^a-z0-9]+/', Str::ascii(Str::lower($texto))))
            ->filter(fn (?string $palabra) => $palabra && strlen($palabra) >= 3 && ! in_array($palabra, $comunes, true))
            ->unique()
            ->values()
            ->all();
    }
}
