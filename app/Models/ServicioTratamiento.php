<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServicioTratamiento extends Pivot
{
    public $incrementing = true;

    public $timestamps = false;

    protected $table = 'servicios_tratamientos';

    /**
     * La tabla tiene created_at (lo llena la base) pero no updated_at.
     *
     * Al reordenar una asignación existente, Laravel ve created_at, activa las
     * marcas de tiempo del pivote y toma el nombre updated_at del modelo padre.
     * Devolver null aquí evita que intente escribir esa columna inexistente.
     */
    public function getUpdatedAtColumn()
    {
        return null;
    }
}
