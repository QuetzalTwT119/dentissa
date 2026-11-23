<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exploración clínica odontológica (23/11/2026). La llena la doctora en la
 * primera visita y en los controles. Las opciones viven en estas constantes:
 * agregar una no cambia la base de datos.
 *
 * Odontograma con numeración FDI (la usual en México): 11–48 permanentes y
 * 51–85 temporales. Rojo = por tratar; azul = ya está o ya se hizo.
 */
#[Fillable([
    'id_paciente', 'id_doctor', 'fecha', 'estomatologico', 'atm', 'periodontal', 'higiene_oral',
    'oleary_dientes', 'oleary_superficies_placa', 'pulpares', 'radiografica', 'otros_hallazgos',
    'odontograma', 'diagnosticos',
])]
class ExploracionClinica extends Model
{
    protected $table = 'exploraciones_clinicas';

    /** Examen estomatológico: cada estructura es normal o tiene un hallazgo. */
    public const ESTRUCTURAS = [
        'labios' => 'Labios',
        'carrillos' => 'Carrillos (mejillas)',
        'lengua' => 'Lengua',
        'piso_boca' => 'Piso de la boca',
        'paladar_duro' => 'Paladar duro',
        'paladar_blando' => 'Paladar blando',
        'orofaringe' => 'Orofaringe (garganta)',
        'frenillos' => 'Frenillos',
        'glandulas_salivales' => 'Glándulas salivales',
        'maxilares' => 'Maxilares',
        'musculos_masticadores' => 'Músculos masticadores',
        'ganglios' => 'Ganglios del cuello',
    ];

    /** Lo que presentan las encías; se anota en qué dientes. */
    public const PERIODONTAL = [
        'sangrado' => 'Sangrado',
        'inflamacion' => 'Inflamación (encía roja o hinchada)',
        'recesion' => 'Recesión (encía retraída)',
        'movilidad' => 'Movilidad dental',
        'bolsas' => 'Bolsas periodontales',
        'calculo' => 'Cálculo (sarro)',
    ];

    public const HIGIENE = ['buena' => 'Buena', 'regular' => 'Regular', 'mala' => 'Mala'];

    /** Convenciones del odontograma: [nombre, color, símbolo]. */
    public const ESTADOS_DIENTE = [
        'cariado' => ['Cariado', 'rojo', '●'],
        'extraccion_indicada' => ['Extracción indicada', 'rojo', 'X'],
        'necesita_endodoncia' => ['Necesita endodoncia', 'rojo', '▲'],
        'necesita_sellante' => ['Necesita sellante', 'rojo', 'S'],
        'obturado' => ['Obturado', 'azul', '●'],
        'endodoncia' => ['Con endodoncia', 'azul', '▲'],
        'extraido' => ['Extraído', 'azul', '='],
        'ausente' => ['Ausente', 'azul', 'A'],
        'sano' => ['Sano', 'azul', '✓'],
    ];

    /** Arcadas en el orden en que se ven de frente al paciente. */
    public const ARCADAS = [
        'permanente' => [
            'superior' => [[18, 17, 16, 15, 14, 13, 12, 11], [21, 22, 23, 24, 25, 26, 27, 28]],
            'inferior' => [[48, 47, 46, 45, 44, 43, 42, 41], [31, 32, 33, 34, 35, 36, 37, 38]],
        ],
        'temporal' => [
            'superior' => [[55, 54, 53, 52, 51], [61, 62, 63, 64, 65]],
            'inferior' => [[85, 84, 83, 82, 81], [71, 72, 73, 74, 75]],
        ],
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'estomatologico' => 'array',
            'periodontal' => 'array',
            'pulpares' => 'array',
            'radiografica' => 'array',
            'odontograma' => 'array',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_doctor');
    }

    /** @return list<int> todos los dientes válidos (permanentes y temporales). */
    public static function dientesValidos(): array
    {
        return collect(self::ARCADAS)->flatten()->map(fn ($d) => (int) $d)->sort()->values()->all();
    }

    /**
     * Índice de placa de O'Leary: superficies con placa / (dientes × 4) × 100.
     * Menos de 20 % se considera aceptable.
     */
    public function indiceOleary(): ?float
    {
        if (! $this->oleary_dientes || $this->oleary_superficies_placa === null) {
            return null;
        }

        return round(min(100, $this->oleary_superficies_placa / ($this->oleary_dientes * 4) * 100), 1);
    }

    /** @return array<string, int> cuántos dientes hay en cada estado del odontograma. */
    public function resumenOdontograma(): array
    {
        return collect($this->odontograma ?? [])->countBy()->sortDesc()->all();
    }
}
