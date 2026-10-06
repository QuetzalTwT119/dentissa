<?php

namespace App\Models;

use App\Enums\TipoImagen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['titulo', 'descripcion', 'activo', 'orden'])]
class Caso extends Model
{
    use HasFactory;

    protected $table = 'casos';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(Imagen::class, 'referencia_id')->where('tipo', TipoImagen::Caso);
    }
}
