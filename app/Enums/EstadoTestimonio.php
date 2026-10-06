<?php

namespace App\Enums;

enum EstadoTestimonio: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
}
