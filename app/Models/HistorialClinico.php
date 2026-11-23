<?php

namespace App\Models;

use App\Enums\TipoConsulta;
use App\Enums\TipoImagen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id_paciente', 'id_cita', 'id_doctor', 'fecha_consulta', 'motivo_consulta',
    'diagnostico', 'tratamiento_realizado', 'dientes', 'medicamentos_recetados', 'observaciones',
    'notas_doctor', 'tipo_consulta',
])]
class HistorialClinico extends Model
{
    use HasFactory;

    protected $table = 'historial_clinico';

    protected function casts(): array
    {
        return [
            'fecha_consulta' => 'date',
            'tipo_consulta' => TipoConsulta::class,
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_doctor');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(Imagen::class, 'referencia_id')->where('tipo', TipoImagen::HistorialClinico);
    }
}
