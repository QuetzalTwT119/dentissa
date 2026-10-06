<?php

namespace App\Enums;

enum TipoConsulta: string
{
    case PrimeraVez = 'primera_vez';
    case Seguimiento = 'seguimiento';
    case Urgencia = 'urgencia';
    case Control = 'control';
}
