<?php

namespace App\Models;

use App\Enums\TipoOferta;
use App\Enums\TipoValorOferta;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'titulo', 'descripcion', 'id_tratamiento', 'tipo', 'valor_descuento', 'tipo_valor',
    'precio_especial', 'fecha_inicio', 'fecha_fin', 'cupo_total', 'cupos_utilizados',
    'imagen', 'activa',
])]
class Oferta extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tipo' => TipoOferta::class,
            'tipo_valor' => TipoValorOferta::class,
            'valor_descuento' => 'decimal:2',
            'precio_especial' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'activa' => 'boolean',
        ];
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }
}
