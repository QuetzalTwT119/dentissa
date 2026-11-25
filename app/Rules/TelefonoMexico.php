<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Celular de México que puede existir (QA en producción, 25/11/2026: se guardó
 * un paciente con «0000000000» y a ese número nunca le llegaría el recordatorio).
 *
 * Acepta espacios, guiones y la lada +52; revisa los 10 dígitos:
 * - ningún número de México empieza con 0 ni con 1 (las ladas van del 2 al 9);
 * - no puede ser un mismo dígito repetido (1111111111, 5555555555…).
 *
 * El formato (10 dígitos) lo siguen revisando las reglas de cada formulario.
 */
class TelefonoMexico implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digitos = preg_replace('/\D/', '', (string) $value);

        // «+52 755 113 1833» → los 10 dígitos nacionales.
        if (strlen($digitos) === 12 && str_starts_with($digitos, '52')) {
            $digitos = substr($digitos, 2);
        }

        if (strlen($digitos) !== 10) {
            return; // El largo lo valida la regla de formato del formulario.
        }

        if (in_array($digitos[0], ['0', '1'], true) || preg_match('/^(\d)\1{9}$/', $digitos)) {
            $fail('Ese número no parece un celular real. Revisa los 10 dígitos, por ejemplo 755 113 1833.');
        }
    }
}
