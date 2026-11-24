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

    public function etiqueta(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::Confirmada => 'Confirmada',
            self::EnCurso => 'En curso',
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
            self::NoAsistio => 'No asistió',
        };
    }

    /** Clases Tailwind de la insignia; las comparten la lista y la semana. */
    public function claseInsignia(): string
    {
        return match ($this) {
            self::Agendada => 'bg-amber-50 text-amber-800 border border-amber-200/70',
            self::Confirmada => 'bg-sky-50 text-sky-800 border border-sky-200/70',
            self::EnCurso => 'bg-brand-50 text-brand-800 border border-brand-200/70',
            self::Completada => 'bg-sage-50 text-sage-500 border border-sage-500/25',
            self::Cancelada => 'bg-shell text-muted border border-line/70',
            self::NoAsistio => 'bg-rose-50 text-rose-800 border border-rose-200/70',
        };
    }

    /** Canceladas e inasistencias quedan como historial, atenuadas en la agenda. */
    public function esInactiva(): bool
    {
        return in_array($this, [self::Cancelada, self::NoAsistio], true);
    }

    /**
     * Citas que todavía tienen algo por hacer (agendada, confirmada o en curso).
     * Una completada ya no es "activa" (QA 24/11/2026: el día decía "4 citas
     * activas" con 2 ya completadas).
     */
    public function esPorAtender(): bool
    {
        return in_array($this, [self::Agendada, self::Confirmada, self::EnCurso], true);
    }
}
