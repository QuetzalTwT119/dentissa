<?php

namespace App\Support;

/**
 * Iniciales para avatares sin foto.
 *
 * substr() corta por bytes y rompe las letras con acento o los emoji
 * ("Ñandú" → "�"). Aquí se toma la primera LETRA real de cada parte,
 * con soporte multibyte, y se ignoran emoji y símbolos.
 */
class Iniciales
{
    /** "María Guadalupe" + "Wiśniewska-Kowalczyk" → "MW"; "😊 Ana" + "Pérez" → "AP". */
    public static function de(?string $nombre, ?string $apellido = null): string
    {
        $primera = self::primeraLetra($nombre);
        $segunda = self::primeraLetra($apellido);

        // Sin apellido: usar la segunda palabra del nombre, si existe.
        if ($segunda === '') {
            $palabras = self::palabras($nombre);
            $segunda = isset($palabras[1]) ? mb_substr($palabras[1], 0, 1) : '';
        }

        $iniciales = mb_strtoupper($primera.$segunda);

        return $iniciales !== '' ? $iniciales : '?';
    }

    /** Para textos con el nombre completo junto: primeras letras de las dos primeras palabras. */
    public static function deTexto(?string $texto): string
    {
        $palabras = self::palabras($texto);

        return self::de($palabras[0] ?? null, $palabras[1] ?? null);
    }

    private static function primeraLetra(?string $texto): string
    {
        $palabras = self::palabras($texto);

        return isset($palabras[0]) ? mb_substr($palabras[0], 0, 1) : '';
    }

    /** @return list<string> palabras que empiezan con letra (se descartan emoji y signos). */
    private static function palabras(?string $texto): array
    {
        preg_match_all('/\p{L}[\p{L}\p{M}\'’-]*/u', (string) $texto, $coincidencias);

        return $coincidencias[0];
    }
}
