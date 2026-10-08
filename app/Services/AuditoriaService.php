<?php

namespace App\Services;

use App\Models\LogAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra acciones relevantes sin copiar el contenido clínico sensible.
 *
 * Los controladores entregan únicamente metadatos seguros para cada dominio.
 * Así podemos reconstruir quién hizo qué sin duplicar diagnósticos,
 * medicamentos u observaciones dentro de la bitácora.
 */
class AuditoriaService
{
    public function registrar(
        string $accion,
        Model $modelo,
        array $datosAnteriores = [],
        array $datosNuevos = [],
        ?Request $request = null,
    ): void {
        try {
            $request ??= app()->bound('request') ? app(Request::class) : null;
            $usuario = $request?->user();

            LogAuditoria::create([
                'id_usuario' => $usuario?->id,
                'accion' => $accion,
                'tabla' => $modelo->getTable(),
                'registro_id' => $modelo->getKey(),
                'datos_anteriores' => $datosAnteriores ?: null,
                'datos_nuevos' => $datosNuevos ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (Throwable $e) {
            // La auditoría no debe tumbar una cita ya guardada, pero el fallo
            // sí debe quedar visible para poder corregir la infraestructura.
            Log::error('No se pudo escribir la auditoría de Dentissa', [
                'accion' => $accion,
                'tabla' => $modelo->getTable(),
                'registro_id' => $modelo->getKey(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
