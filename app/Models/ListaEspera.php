<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nombre', 'telefono', 'email', 'id_servicio', 'id_tratamiento', 'fecha_preferida', 'franja_horaria', 'estado',
    'contactado_por', 'fecha_contacto', 'notas',
])]
class ListaEspera extends Model
{
    use HasFactory;

    protected $table = 'lista_espera';

    protected function casts(): array
    {
        return [
            'fecha_preferida' => 'date',
            'fecha_contacto' => 'datetime',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }
}
