<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre', 'descripcion', 'categoria', 'duracion_estimada', 'precio_referencia',
    'sesiones_requeridas', 'requiere_autorizacion', 'activo', 'imagen',
])]
class Tratamiento extends Model
{
    use HasFactory;

    /** Categorías que ofrece el panel; el catálogo base usa estas mismas. */
    public const CATEGORIAS = ['Diagnóstico', 'Preventiva', 'Restauradora', 'Estética', 'Quirúrgica'];

    protected function casts(): array
    {
        return [
            'precio_referencia' => 'decimal:2',
            'requiere_autorizacion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicios_tratamientos', 'id_tratamiento', 'id_servicio')
            ->using(ServicioTratamiento::class)
            ->withPivot('orden');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_tratamiento');
    }

    public function ofertas(): HasMany
    {
        return $this->hasMany(Oferta::class, 'id_tratamiento');
    }
}
