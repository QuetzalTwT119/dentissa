<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un festivo que la doctora quitó de la agenda porque sí va a trabajar.
 * Ver dentissa:bloquear-festivos.
 */
class FestivoOmitido extends Model
{
    protected $table = 'festivos_omitidos';

    protected $fillable = ['fecha', 'omitido_por'];

    protected function casts(): array
    {
        return ['fecha' => 'date'];
    }
}
