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

    /** "$3,500" o "$1,250.50"; null si no tiene precio. */
    public function precioFormateado(): ?string
    {
        if (! ($this->precio_referencia > 0)) {
            return null;
        }

        $precio = (float) $this->precio_referencia;

        return '$'.number_format($precio, fmod($precio, 1.0) == 0.0 ? 0 : 2);
    }

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
