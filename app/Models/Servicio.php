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

    /**
     * Foto para el sitio público: la que subió la clínica o, si no hay, la
     * aprobada en config/dentissa.php para esa especialidad (y la del inicio
     * como último recurso).
     */
    public function fotoPublica(): string
    {
        if ($this->imagen) {
            return '/storage/'.ltrim($this->imagen, '/');
        }

        return asset(config('dentissa.imagenes.servicios.'.$this->nombre) ?? config('dentissa.imagenes.hero'));
    }

    /** Descripción para el sitio público: la escrita por la clínica o la base de config/dentissa.php. */
    public function descripcionPublica(): ?string
    {
        return filled($this->descripcion)
            ? $this->descripcion
            : (config('dentissa.descripciones_servicios')[$this->nombre] ?? null);
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
