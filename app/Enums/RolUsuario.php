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

    /** Doctores asociados: solo lo clínico (25/11/2026). */
    case Doctor = 'doctor';

    /** Recepción: agenda, directorio de pacientes, cobros y mensajes (25/11/2026). */
    case Asistente = 'asistente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Doctora titular',
            self::Doctor => 'Doctor',
            self::Asistente => 'Asistente',
        };
    }

    /**
     * Qué puede hacer cada rol, en palabras de la doctora (25/11/2026). Se muestra
     * al crear o editar un usuario. Debe coincidir con config/permisos.php;
     * PermisosPorRolTest lo comprueba.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Acceso total: además de lo clínico, administra usuarios, horarios, precios, contenido de la portada, reportes y control de actividades.',
            self::Doctor => 'Lo clínico: agenda, pacientes con su expediente, historia clínica, recetas y cobros. No administra usuarios, horarios, precios, portada ni reportes.',
            self::Asistente => 'Recepción: agenda, alta y datos de contacto de pacientes, cuestionario de historia clínica, cobros y mensajes. No ve expedientes, consultas ni recetas.',
        };
    }

    public function puede(string $modulo): bool
    {
        return Permisos::rolPuede($this, $modulo);
    }
}
