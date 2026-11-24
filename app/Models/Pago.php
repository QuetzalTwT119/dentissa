<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de un paciente (24/11/2026): cuánto, cómo y cuándo. Opcionalmente
 * ligado a una cita. Solo registro: el cobro se hace en el consultorio.
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = ['id_paciente', 'id_cita', 'monto', 'metodo', 'fecha', 'concepto', 'registrado_por'];

    public const METODOS = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha' => 'date',
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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function metodoTexto(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }

    /** "$1,250" o "$1,250.50". */
    public static function dinero(float|string|null $monto): string
    {
        $valor = (float) $monto;

        return '$'.number_format($valor, fmod($valor, 1.0) ? 2 : 0, '.', ',');
    }
}
