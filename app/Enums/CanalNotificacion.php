<?php

namespace App\Enums;

enum CanalNotificacion: string
{
    case CitaAgendada = 'cita_agendada';
    case CitaConfirmada = 'cita_confirmada';
    case CitaRecordatorio = 'cita_recordatorio';
    case CitaCancelada = 'cita_cancelada';
    case CitaReagendada = 'cita_reagendada';
    case Promocion = 'promocion';
    case General = 'general';
}
