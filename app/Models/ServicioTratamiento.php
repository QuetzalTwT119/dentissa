<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServicioTratamiento extends Pivot
{
    public $incrementing = true;

    public $timestamps = false;

    protected $table = 'servicios_tratamientos';
}
