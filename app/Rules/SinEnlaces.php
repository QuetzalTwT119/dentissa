<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Bloquea enlaces, correos, código HTML y texto repetido de relleno en textos
 * que se publican en la portada (21/11/2026). La portada no debe mandar al
 * paciente a sitios externos ni mostrar spam.
 */
class SinEnlaces implements ValidationRule
{
    private const ENLACE = '~(https?://|www\.|\b[\w-]+\.(com|net|org|mx|info|biz|xyz|io|co|me|ly|site|online|link|click|top|shop|store)\b)~iu';

    private const CORREO = '~[\w.+-]+@[\w-]+\.[\w.]+~u';

    private const HTML = '~<\s*/?\s*[a-z][^>]*>~i';

    private const REPETIDO = '~(.)\1{5,}~u';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (preg_match(self::ENLACE, $value) || preg_match(self::CORREO, $value)) {
            $fail('El campo :attribute no puede llevar enlaces ni correos.');
        } elseif (preg_match(self::HTML, $value)) {
            $fail('El campo :attribute no puede llevar código.');
        } elseif (preg_match(self::REPETIDO, $value)) {
            $fail('El campo :attribute tiene caracteres repetidos; escríbelo como lo leerá el paciente.');
        }
    }
}
