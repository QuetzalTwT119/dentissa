<?php

namespace App\Observers;

use App\Models\LogAuditoria;
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
        $campos = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));

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

        LogAuditoria::query()->create([
            'id_usuario' => Auth::id(),
            'accion' => $accion,
            'tabla' => $model->getTable(),
            'registro_id' => $model->getKey(),
            // Solo registramos los nombres de los campos, nunca sus valores.
            'datos_nuevos' => $campos === [] ? null : ['campos_modificados' => $campos],
            'fecha' => now(),
        ]);
    }
}
