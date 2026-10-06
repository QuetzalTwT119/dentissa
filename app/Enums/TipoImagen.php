<?php

namespace App\Enums;

enum TipoImagen: string
{
    case Galeria = 'galeria';
    case Servicio = 'servicio';
    case Tratamiento = 'tratamiento';
    case Testimonio = 'testimonio';
    case Perfil = 'perfil';
    case HistorialClinico = 'historial_clinico';
    case Caso = 'caso';
}
