<?php

namespace App\Enums;

enum TipoValorOferta: string
{
    case Porcentaje = 'porcentaje';
    case MontoFijo = 'monto_fijo';
}
