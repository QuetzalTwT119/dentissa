<?php

namespace App\Models;

use App\Enums\EstadoTestimonio;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_paciente', 'id_cita', 'calificacion', 'comentario', 'estado', 'fecha_publicacion'])]
class Testimonio extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estado' => EstadoTestimonio::class,
            'fecha_publicacion' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}
