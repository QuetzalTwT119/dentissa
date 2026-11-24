<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Plan de tratamiento de un paciente (24/11/2026): pasos pendientes o hechos.
 * El avance no se escribe a mano: sale de cuántos pasos están hechos.
 */
class PlanTratamiento extends Model
{
    protected $table = 'planes_tratamiento';

    protected $fillable = ['id_paciente', 'id_doctor', 'titulo', 'estado', 'notas'];

    public const ESTADOS = [
        'activo' => 'En curso',
        'terminado' => 'Terminado',
        'cancelado' => 'Cancelado',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_doctor');
    }

    public function pasos(): HasMany
    {
        return $this->hasMany(PlanTratamientoPaso::class, 'id_plan')->orderBy('orden')->orderBy('id');
    }

    /** Porcentaje de pasos hechos (0 si no tiene pasos). */
    public function avance(): int
    {
        $total = $this->pasos->count();

        return $total === 0 ? 0 : (int) round($this->pasos->whereNotNull('hecho_en')->count() * 100 / $total);
    }

    public function costoTotal(): float
    {
        return (float) $this->pasos->sum('costo');
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
