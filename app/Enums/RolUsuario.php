<?php

namespace App\Enums;

use App\Support\Permisos;

/**
 * Roles del personal (22/11/2026: antes solo "admin" y "asistente").
 * Qué puede cada uno se define en config/permisos.php.
 */
enum RolUsuario: string
{
    /** La doctora dueña: acceso total. */
    case SuperAdmin = 'super_admin';

    /** Doctores asociados: por ahora lo mismo que el antiguo "admin". */
    case Doctor = 'doctor';

    /** Por ahora solo el panel de inicio. */
    case Asistente = 'asistente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Doctora titular',
            self::Doctor => 'Doctor',
            self::Asistente => 'Asistente',
        };
    }

    public function puede(string $modulo): bool
    {
        return Permisos::rolPuede($this, $modulo);
    }
}
