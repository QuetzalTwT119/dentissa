<?php

namespace App\Enums;

enum EstadoCita: string
{
    case Agendada = 'agendada';
    case Confirmada = 'confirmada';
    case EnCurso = 'en_curso';
    case Completada = 'completada';
    case Cancelada = 'cancelada';
    case NoAsistio = 'no_asistio';
}
