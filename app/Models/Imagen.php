<?php

namespace App\Models;

use App\Enums\TipoImagen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['titulo', 'descripcion', 'url', 'tipo', 'referencia_id', 'orden', 'activa'])]
class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';

    protected function casts(): array
    {
        return [
            'tipo' => TipoImagen::class,
            'activa' => 'boolean',
        ];
    }
}
