<?php

namespace App\Models;

use App\Enums\CanalNotificacion;
use App\Enums\TipoNotificacion;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_usuario', 'id_paciente', 'id_cita', 'tipo', 'canal', 'titulo', 'mensaje', 'leido', 'fecha_envio', 'fecha_lectura', 'id_externo_meta'])]
class Notificacion extends Model
{
    use HasFactory;

    // Sin esto, Eloquent adivina "notificacions" (pluralización en inglés) en
    // vez de "notificaciones" — la tabla real. Nada había llamado a este
    // modelo hasta ahora, así que el error nunca se había manifestado.
    protected $table = 'notificaciones';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'tipo' => TipoNotificacion::class,
            'canal' => CanalNotificacion::class,
            'leido' => 'boolean',
            'fecha_envio' => 'datetime',
            'fecha_lectura' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }
}
