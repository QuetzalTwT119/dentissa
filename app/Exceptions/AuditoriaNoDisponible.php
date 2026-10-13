<?php

namespace App\Exceptions;

use RuntimeException;

class AuditoriaNoDisponible extends RuntimeException
{
    public static function porError(\Throwable $exception): self
    {
        return new self('No fue posible registrar la auditoría.', previous: $exception);
    }
}
