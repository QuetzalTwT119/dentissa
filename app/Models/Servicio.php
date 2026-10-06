<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'descripcion', 'imagen', 'icono', 'activo', 'orden'])]
class Servicio extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function tratamientos(): BelongsToMany
    {
        return $this->belongsToMany(Tratamiento::class, 'servicios_tratamientos', 'id_servicio', 'id_tratamiento')
            ->using(ServicioTratamiento::class)
            ->withPivot('orden');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_servicio');
    }
}
