<?php

namespace App\Events;

use App\Models\NotificacionSistema;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificacionSistemaCreada implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(private readonly NotificacionSistema $notificacion) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->notificacion->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notificacion.creada';
    }

    public function broadcastWith(): array
    {
        return $this->notificacion->datosEnVivo() + [
            'no_vistas' => NotificacionSistema::query()
                ->where('user_id', $this->notificacion->user_id)
                ->whereNull('vista_en')
                ->count(),
        ];
    }
}
