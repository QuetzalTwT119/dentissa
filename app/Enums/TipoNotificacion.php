<?php

namespace App\Enums;

enum TipoNotificacion: string
{
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Push = 'push';
}
