<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Día u horas en que no se agenda (24/11/2026): vacaciones, imprevistos o
 * mantenimiento. Sin hora_inicio/hora_fin bloquea todos los días del rango;
 * con horas, bloquea ese tramo (solo dentro de un mismo día).
 *
 * La disponibilidad (DisponibilidadService) lo descuenta en la agenda en línea,
 * en Nueva cita del panel, al reagendar y en los asistentes.
 */
class BloqueoAgenda extends Model
{
    protected $table = 'bloqueos_agenda';

    protected $fillable = ['fecha_inicio', 'fecha_fin', 'hora_inicio', 'hora_fin', 'motivo', 'creado_por'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function todoElDia(): bool
    {
        return $this->hora_inicio === null || $this->hora_fin === null;
    }

    /** Bloqueos que tocan esa fecha (Y-m-d). */
    public function scopeDelDia(Builder $query, string $fecha): Builder
    {
        return $query->whereDate('fecha_inicio', '<=', $fecha)->whereDate('fecha_fin', '>=', $fecha);
    }

    /** Bloqueos que aún no terminan, del más próximo al más lejano. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereDate('fecha_fin', '>=', now()->toDateString())->orderBy('fecha_inicio')->orderBy('hora_inicio');
    }

    /**
     * Tramos bloqueados de un día como [inicio, fin). Un bloqueo de día completo
     * ocupa de 00:00 a 24:00.
     *
     * @return Collection<int, array{0: Carbon, 1: Carbon}>
     */
    public static function tramosDelDia(string $fecha): Collection
    {
        return static::delDia($fecha)->get()->map(function (self $bloqueo) use ($fecha) {
            $dia = Carbon::parse($fecha)->startOfDay();

            return $bloqueo->todoElDia()
                ? [$dia->copy(), $dia->copy()->addDay()]
                : [$dia->copy()->setTimeFromTimeString($bloqueo->hora_inicio), $dia->copy()->setTimeFromTimeString($bloqueo->hora_fin)];
        });
    }

    /** ¿El tramo [inicio, fin) choca con algún bloqueo? */
    public static function chocaCon(Collection $tramos, CarbonInterface $inicio, CarbonInterface $fin): bool
    {
        return $tramos->contains(fn (array $tramo) => $inicio->lt($tramo[1]) && $fin->gt($tramo[0]));
    }

    /** "25 dic 2026", "24 – 26 dic 2026 · 10:00 a 12:00"… para listas y avisos. */
    public function descripcion(): string
    {
        $inicio = $this->fecha_inicio->copy()->locale('es');
        $fin = $this->fecha_fin->copy()->locale('es');

        $fechas = $inicio->isSameDay($fin)
            ? $inicio->translatedFormat('D j M Y')
            : $inicio->translatedFormat('j M').' – '.$fin->translatedFormat('j M Y');

        return $this->todoElDia()
            ? $fechas.' · todo el día'
            : $fechas.' · '.substr($this->hora_inicio, 0, 5).' a '.substr($this->hora_fin, 0, 5);
    }
}
