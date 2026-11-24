<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consentimiento informado de un procedimiento (24/11/2026). Se imprime para
 * firmarlo a mano; el registro guarda lo que se le explicó al paciente.
 */
class Consentimiento extends Model
{
    protected $table = 'consentimientos';

    protected $fillable = ['id_paciente', 'id_doctor', 'procedimiento', 'dientes', 'beneficios', 'riesgos', 'alternativas'];

    /** Textos de partida: la doctora los ajusta a cada caso antes de imprimir. */
    public const BENEFICIOS = 'Aliviar el dolor o la molestia, detener el avance del problema y recuperar la función y la estética de la boca.';

    public const RIESGOS = 'Dolor, inflamación, sangrado leve, sensibilidad o molestias temporales después del procedimiento. Con menor frecuencia: infección, reacción a la anestesia o a los medicamentos, adormecimiento temporal de labios o lengua, o la necesidad de un tratamiento adicional.';

    public const ALTERNATIVAS = 'No realizar el procedimiento, con el riesgo de que el problema avance; u otras opciones de tratamiento que la doctora me explicó.';

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_doctor');
    }
}
