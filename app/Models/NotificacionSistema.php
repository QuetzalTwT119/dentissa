<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'id_cita', 'evento', 'titulo', 'mensaje', 'url', 'leida_en', 'vista_en', 'atendida_en'])]
class NotificacionSistema extends Model
{
    protected $table = 'notificaciones_sistema';

    /** Eventos que piden que alguien del equipo haga algo (llamar, reacomodar, revisar). */
    public const EVENTOS_PRIORITARIOS = ['cancelada', 'no_asistio', 'cancelada_paciente', 'reagendar_paciente', 'respaldo_fallido'];

    /** Cantidad de tonos de avatar definidos en app.css (.avatar-tono-0 … -5). */
    private const TONOS_AVATAR = 6;

    protected function casts(): array
    {
        return [
            'leida_en' => 'datetime',
            'vista_en' => 'datetime',
            'atendida_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    /** Avisos prioritarios que nadie ha marcado como atendidos. */
    public function scopePorAtender(Builder $query): Builder
    {
        return $query->whereIn('evento', self::EVENTOS_PRIORITARIOS)->whereNull('atendida_en');
    }

    /** Lo demás: informativos, o prioritarios que ya se atendieron. */
    public function scopeInformativas(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNotIn('evento', self::EVENTOS_PRIORITARIOS)->orWhereNotNull('atendida_en'));
    }

    /** Etiqueta breve para que el equipo identifique el origen del aviso de un vistazo. */
    public function etiquetaEvento(): string
    {
        return match ($this->evento) {
            'agendada' => 'Nueva cita',
            'reagendada' => 'Cambio de horario',
            'confirmada' => 'Confirmación',
            'cancelada' => 'Cancelación',
            'iniciada' => 'Atención en curso',
            'no_asistio' => 'Inasistencia',
            'completada' => 'Atención completada',
            'asistencia_confirmada' => 'Respuesta del paciente',
            'cancelada_paciente' => 'Cancelación del paciente',
            'reagendar_paciente' => 'Horario liberado',
            'reagendada_paciente' => 'Nuevo horario del paciente',
            'respaldo_fallido' => 'Respaldo fallido',
            default => 'Sistema',
        };
    }

    /** Los cambios que pueden requerir reacción inmediata se destacan visualmente. */
    public function esPrioritaria(): bool
    {
        return in_array($this->evento, self::EVENTOS_PRIORITARIOS, true);
    }

    public function requiereAccion(): bool
    {
        return $this->esPrioritaria() && $this->atendida_en === null;
    }

    /** Nombre del paciente detrás del aviso; null en avisos del sistema. */
    public function persona(): ?string
    {
        $nombre = $this->cita?->paciente?->nombre_completo;

        return filled($nombre) ? $nombre : null;
    }

    /** Dos iniciales: "Ana Sofía Mendoza" → "AS". */
    public function iniciales(): string
    {
        $palabras = preg_split('/\s+/u', trim((string) $this->persona()), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return mb_strtoupper(collect($palabras)->take(2)->map(fn (string $p) => mb_substr($p, 0, 1))->implode(''));
    }

    /**
     * El mismo paciente conserva siempre el mismo color (se deriva del nombre),
     * así el equipo lo reconoce en avisos distintos.
     */
    public function tonoAvatar(): int
    {
        return crc32(mb_strtolower((string) $this->persona())) % self::TONOS_AVATAR;
    }

    /** Qué pasó, para la insignia del avatar: [ícono, tono]. */
    public function insignia(): array
    {
        return match ($this->evento) {
            'agendada', 'reagendada', 'reagendada_paciente' => ['calendario', 'nueva'],
            'confirmada', 'asistencia_confirmada', 'completada', 'iniciada' => ['palomita', 'ok'],
            'cancelada', 'cancelada_paciente', 'no_asistio' => ['tache', 'baja'],
            'reagendar_paciente' => ['ciclo', 'alerta'],
            'respaldo_fallido' => ['alerta', 'baja'],
            default => ['nube', 'sistema'],
        };
    }

    /** Teléfono del paciente en formato marcable, si lo hay. */
    public function telefonoPaciente(): ?string
    {
        $telefono = preg_replace('/[^\d+]/', '', (string) $this->cita?->paciente?->telefono);

        return $telefono !== '' ? $telefono : null;
    }

    /** Acción principal de un aviso por atender: [texto, url]. */
    public function accionPrincipal(): array
    {
        if ($this->cita) {
            // La agenda abre en el día de la cita: ahí se ve el hueco liberado o la inasistencia.
            return ['Ver en la agenda', route('citas.index', ['fecha' => $this->cita->fecha_hora->toDateString()], false)];
        }

        return ['Ver detalle', $this->url ?: route('notificaciones.index', [], false)];
    }

    /** Fragmento HTML del panel de la campana; lo reutiliza la actualización en vivo. */
    public function htmlPanel(): string
    {
        $this->loadMissing('cita.paciente');

        return view('notificaciones.partials.aviso-panel', ['notificacion' => $this])->render();
    }

    /** Datos que reciben la campana y el aviso emergente al llegar un aviso nuevo. */
    public function datosEnVivo(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'url' => $this->url ?: route('notificaciones.index', [], false),
            'leida_url' => route('notificaciones.leida', $this, false),
            'creada' => $this->created_at->toIso8601String(),
            'por_atender' => $this->requiereAccion(),
            'html' => $this->htmlPanel(),
        ];
    }
}
