<?php

namespace App\Observers;

use App\Models\LogAuditoria;
use App\Support\BitacoraPendiente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Conserva un rastro operativo sin duplicar información clínica sensible.
 */
class AuditoriaObserver
{
    public function created(Model $model): void
    {
        $this->registrar($model, 'creado', array_keys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $campos = array_values(array_diff(array_keys($model->getChanges()), ['updated_at', 'remember_token']));

        if ($campos !== []) {
            $this->registrar($model, 'actualizado', $campos);
        }
    }

    public function deleted(Model $model): void
    {
        $this->registrar($model, 'eliminado', []);
    }

    private function registrar(Model $model, string $accion, array $campos): void
    {
        if (! Auth::check() || $model instanceof LogAuditoria) {
            return;
        }

        $usuario = Auth::user();

        // Pendiente: se escribe al terminar la petición solo si este cambio no
        // tuvo ya su evento con detalle (BitacoraPendiente, 22/11/2026). Ahora
        // también guarda quién y con qué rol (antes salía "Personal").
        app(BitacoraPendiente::class)->agregar([
            'id_usuario' => $usuario->getKey(),
            'actor_user_id' => $usuario->getKey(),
            'actor_nombre' => trim($usuario->nombre.' '.$usuario->apellido),
            'actor_rol' => $usuario->rol?->value,
            'accion' => $accion,
            'tabla' => $model->getTable(),
            'registro_id' => $model->getKey(),
            // Solo registramos los nombres de los campos, nunca sus valores.
            'datos_nuevos' => $campos === [] ? null : ['campos_modificados' => $campos],
            'fecha' => now(),
        ]);
    }
}
