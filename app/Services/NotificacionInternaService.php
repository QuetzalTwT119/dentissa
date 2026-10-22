<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificacionInternaService
{
    public function cambioCita(Cita $cita, string $evento, ?int $actorId = null): void
    {
        $etiquetas = [
            'agendada' => ['Nueva cita registrada', 'Se registró una cita para'],
            'reagendada' => ['Cita reagendada', 'Se cambió la fecha de una cita para'],
            'confirmada' => ['Cita confirmada', 'Se confirmó una cita para'],
            'cancelada' => ['Cita cancelada', 'Se canceló una cita que estaba programada para'],
            'iniciada' => ['Atención iniciada', 'Comenzó la atención de una cita programada para'],
            'no_asistio' => ['Inasistencia registrada', 'Se registró una inasistencia para la cita de'],
            'completada' => ['Cita completada', 'Se completó una cita programada para'],
        ];

        if (! isset($etiquetas[$evento])) {
            return;
        }

        [$titulo, $prefijo] = $etiquetas[$evento];
        $fecha = $cita->fecha_hora->translatedFormat('d M Y, H:i');
        $destinatarios = User::query()
            ->where('activo', true)
            ->whereIn('rol', [RolUsuario::Admin->value, RolUsuario::Asistente->value])
            ->when($actorId, fn ($query) => $query->whereKeyNot($actorId))
            ->pluck('id');

        try {
            foreach ($destinatarios as $userId) {
                $notificacion = NotificacionSistema::create([
                    'user_id' => $userId,
                    'evento' => $evento,
                    'titulo' => $titulo,
                    'mensaje' => "{$prefijo} {$fecha}.",
                    // La ruta relativa conserva la sesión aunque se acceda por localhost,
                    // IP local o un dominio de producción distinto.
                    'url' => route('citas.index', [], false),
                ]);

                try {
                    event(new NotificacionSistemaCreada($notificacion));
                } catch (Throwable $exception) {
                    Log::warning('No se pudo transmitir una notificación interna en tiempo real', [
                        'id_notificacion' => $notificacion->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo guardar una notificación interna de cita', [
                'id_cita' => $cita->id,
                'evento' => $evento,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
