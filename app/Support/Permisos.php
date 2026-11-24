<?php

namespace App\Support;

use App\Enums\RolUsuario;
use Illuminate\Support\Str;

/**
 * Lee el mapa central de permisos (config/permisos.php). Ver ese archivo para
 * cambiar qué puede cada rol; aquí solo está la lógica para consultarlo.
 */
class Permisos
{
    /** ¿El rol tiene el módulo? Un módulo que no existe en el mapa nunca se concede. */
    public static function rolPuede(RolUsuario $rol, string $modulo): bool
    {
        if (! array_key_exists($modulo, config('permisos.modulos', []))) {
            return false;
        }

        // Módulo apagado en config/dentissa.php → modulos (p. ej. "pagos"): nadie lo tiene.
        if (config()->has("dentissa.modulos.$modulo") && ! config("dentissa.modulos.$modulo")) {
            return false;
        }

        $modulos = config('permisos.roles.'.$rol->value, []);

        return in_array('*', $modulos, true) || in_array($modulo, $modulos, true);
    }

    /** Módulo al que pertenece una ruta por su nombre, o null si no está mapeada. */
    public static function moduloDeRuta(?string $ruta): ?string
    {
        if (blank($ruta)) {
            return null;
        }

        foreach (config('permisos.modulos', []) as $modulo => $patrones) {
            if (Str::is($patrones, $ruta)) {
                return $modulo;
            }
        }

        return null;
    }

    /** @return list<RolUsuario> roles que tienen el módulo (p. ej. a quién avisar). */
    public static function rolesCon(string $modulo): array
    {
        return array_values(array_filter(RolUsuario::cases(), fn (RolUsuario $rol) => self::rolPuede($rol, $modulo)));
    }

    /** @return list<string> valores de rol que tienen el módulo, para consultas a la base. */
    public static function valoresCon(string $modulo): array
    {
        return array_map(fn (RolUsuario $rol) => $rol->value, self::rolesCon($modulo));
    }
}
