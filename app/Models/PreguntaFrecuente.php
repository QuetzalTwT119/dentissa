<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pregunta', 'respuesta', 'variantes', 'activa', 'orden'])]
class PreguntaFrecuente extends Model
{
    use HasFactory;

    // Explícito: la pluralización automática de "PreguntaFrecuente" no cae en
    // "preguntas_frecuentes" (ver el mismo problema resuelto en Notificacion).
    protected $table = 'preguntas_frecuentes';

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }
}
