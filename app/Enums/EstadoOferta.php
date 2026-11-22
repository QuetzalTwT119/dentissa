<?php

namespace App\Enums;

/**
 * Ciclo de una oferta (21/11/2026): ninguna se publica directo al guardarla.
 *  - Pendiente: recién creada o editada; no se ve en la portada.
 *  - Activa: el administrador la revisó y la publicó.
 *  - Inactiva: oculta; sigue en el panel para editarla o volver a publicarla.
 */
enum EstadoOferta: string
{
    case Pendiente = 'pendiente';
    case Activa = 'activa';
    case Inactiva = 'inactiva';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'En revisión',
            self::Activa => 'Publicada',
            self::Inactiva => 'Oculta',
        };
    }
}
