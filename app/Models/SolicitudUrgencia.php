<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'nombre', 'telefono', 'email', 'motivo', 'senales_reportadas', 'estado', 'contactado_por', 'fecha_contacto',
])]
class SolicitudUrgencia extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_urgencia';

    protected function casts(): array
    {
        return [
            'senales_reportadas' => 'array',
            'fecha_contacto' => 'datetime',
        ];
    }
}
