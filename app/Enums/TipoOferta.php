<?php

namespace App\Enums;

enum TipoOferta: string
{
    case Descuento = 'descuento';
    case Paquete = 'paquete';
    case Bonificacion = 'bonificacion';
}
