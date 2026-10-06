<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nombre', 'email', 'telefono', 'mensaje', 'leido'])]
class MensajeContacto extends Model
{
    use HasFactory;

    protected $table = 'mensajes_contacto';

    protected function casts(): array
    {
        return [
            'leido' => 'boolean',
        ];
    }
}
