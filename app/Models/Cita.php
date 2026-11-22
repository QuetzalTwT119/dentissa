<?php

namespace App\Models;

use App\Enums\EstadoCita;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id_paciente', 'id_servicio', 'id_tratamiento', 'id_oferta', 'fecha_hora', 'duracion_estimada',
    'estado', 'motivo', 'observaciones', 'clave_idempotencia', 'confirmada_por', 'fecha_confirmacion',
    'cancelada_por', 'fecha_cancelacion', 'motivo_cancelacion', 'recordatorio_enviado_en', 'asistencia_confirmada_en',
])]
class Cita extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'estado' => EstadoCita::class,
            'fecha_confirmacion' => 'datetime',
            'fecha_cancelacion' => 'datetime',
            'recordatorio_enviado_en' => 'datetime',
            'asistencia_confirmada_en' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }

    /** Oferta con la que se agendó (21/11/2026): la doctora la aplica al cobrar. */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class, 'id_oferta');
    }

    public function confirmadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function testimonios(): HasMany
    {
        return $this->hasMany(Testimonio::class, 'id_cita');
    }

    public function historialClinico(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_cita');
    }
}
