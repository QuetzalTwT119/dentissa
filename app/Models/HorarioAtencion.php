<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

#[Fillable(['dia_semana', 'abierto', 'hora_apertura', 'hora_cierre', 'capacidad_simultanea'])]
class HorarioAtencion extends Model
{
    protected $table = 'horarios_atencion';

    protected function casts(): array
    {
        return [
            'abierto' => 'boolean',
        ];
    }

    public static function paraFecha(string $fecha, bool $lock = false): ?self
    {
        return static::where('dia_semana', Carbon::parse($fecha)->dayOfWeek)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->first();
    }
}
