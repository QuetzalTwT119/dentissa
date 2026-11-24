<?php

namespace App\Models;

use App\Enums\EstadoCita;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id_paciente', 'id_servicio', 'id_tratamiento', 'id_oferta', 'fecha_hora', 'duracion_estimada',
    'estado', 'motivo', 'observaciones', 'clave_idempotencia', 'confirmada_por', 'fecha_confirmacion',
    'cancelada_por', 'fecha_cancelacion', 'motivo_cancelacion', 'recordatorio_enviado_en', 'asistencia_confirmada_en',
    'id_cita_nueva',
])]
class Cita extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'estado' => EstadoCita::class,
            'fecha_confirmacion' => 'datetime',
            'fecha_cancelacion' => 'datetime',
            'recordatorio_enviado_en' => 'datetime',
            'asistencia_confirmada_en' => 'datetime',
        ];
    }

    /** Hora en que termina, según su duración. */
    public function fin(): \Carbon\CarbonInterface
    {
        return $this->fecha_hora->copy()->addMinutes((int) $this->duracion_estimada);
    }

    /**
     * Ya pasó su hora de fin sin que nadie la atendiera en el sistema (QA
     * 24/11/2026). La agenda deja de ofrecer «Iniciar» y pregunta directo si
     * el paciente fue: «Atendida» o «No asistió».
     */
    public function yaTermino(): bool
    {
        return in_array($this->estado, [EstadoCita::Agendada, EstadoCita::Confirmada], true)
            && $this->fin()->lte(now());
    }

    /** Se puede iniciar: confirmada, de hoy y sin haber terminado ya su horario. */
    public function sePuedeIniciar(): bool
    {
        return $this->estado === EstadoCita::Confirmada
            && $this->fecha_hora->isToday()
            && ! $this->yaTermino();
    }

    /** Cita que la reemplazó cuando el paciente reagendó desde el recordatorio (23/11/2026). */
    public function citaNueva(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id_cita_nueva');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    /** Pagos ligados a esta cita (24/11/2026). */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_cita');
    }

    /**
     * Precio estimado: el del tratamiento, con su oferta si se agendó con una.
     * null si el tratamiento no tiene precio. Es una referencia para Pagos; lo
     * que se cobra de verdad es lo que se registra.
     */
    public function precioEstimado(): ?float
    {
        $base = (float) ($this->tratamiento?->precio_referencia ?? 0);
        if ($this->oferta) {
            return $this->oferta->precioPara($base);
        }

        return $base > 0 ? $base : null;
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento');
    }

    /** Oferta con la que se agendó (21/11/2026): la doctora la aplica al cobrar. */
    public function oferta(): BelongsTo
    {
        return $this->belongsTo(Oferta::class, 'id_oferta');
    }

    public function confirmadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function testimonios(): HasMany
    {
        return $this->hasMany(Testimonio::class, 'id_cita');
    }

    public function historialClinico(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_cita');
    }
}
