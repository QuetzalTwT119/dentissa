<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'evento', 'titulo', 'mensaje', 'url', 'leida_en', 'vista_en'])]
class NotificacionSistema extends Model
{
    protected $table = 'notificaciones_sistema';

    protected function casts(): array
    {
        return [
            'leida_en' => 'datetime',
            'vista_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Etiqueta breve para que el equipo identifique el origen del aviso de un vistazo. */
    public function etiquetaEvento(): string
    {
        return match ($this->evento) {
            'agendada' => 'Nueva cita',
            'reagendada' => 'Cambio de horario',
            'confirmada' => 'Confirmación',
            'cancelada' => 'Cancelación',
            'iniciada' => 'Atención en curso',
            'no_asistio' => 'Inasistencia',
            'completada' => 'Atención completada',
            default => 'Sistema',
        };
    }

    /** Los cambios que pueden requerir reacción inmediata se destacan visualmente. */
    public function esPrioritaria(): bool
    {
        return in_array($this->evento, ['cancelada', 'no_asistio'], true);
    }
}
