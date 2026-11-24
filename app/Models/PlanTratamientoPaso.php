<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un procedimiento dentro de un plan de tratamiento (24/11/2026). */
class PlanTratamientoPaso extends Model
{
    protected $table = 'plan_tratamiento_pasos';

    protected $fillable = ['id_plan', 'id_tratamiento', 'descripcion', 'dientes', 'costo', 'hecho_en', 'orden'];

    protected function casts(): array
    {
        return [
            'costo' => 'decimal:2',
            'hecho_en' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanTratamiento::class, 'id_plan');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }

    public function hecho(): bool
    {
        return $this->hecho_en !== null;
    }
}
