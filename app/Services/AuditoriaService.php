<?php

namespace App\Services;

use App\Enums\RolUsuario;
use App\Exceptions\AuditoriaNoDisponible;
use App\Models\LogAuditoria;
use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
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
    /**
     * Ejecuta una mutación y su evento de auditoría en una sola transacción.
     * La lista de campos debe ser explícita para no copiar secretos ni datos
     * clínicos a la bitácora.
     */
    public function registrarCambio(
        Request $request,
        string $accion,
        Model $modelo,
        array $campos,
        Closure $mutacion,
        bool $eliminado = false,
    ): mixed {
        return DB::transaction(function () use ($request, $accion, $modelo, $campos, $mutacion, $eliminado) {
            $datosAnteriores = $modelo->exists ? $this->snapshot($modelo, $campos) : [];

            // En bajas de cuentas, escribe el rastro mientras la FK del actor
            // todavía existe. El borrado posterior la vuelve NULL, pero la
            // copia inmutable del actor permite seguir mostrando el evento.
            if ($eliminado) {
                $this->registrar($accion, $modelo, $datosAnteriores, [], $request);

                return $mutacion();
            }

            $resultado = $mutacion();
            $registro = $resultado instanceof Model ? $resultado : $modelo;

            if ($registro->exists) {
                $registro->refresh();
            }

            $datosNuevos = $this->snapshot($registro, $campos);

            $this->registrar($accion, $registro, $datosAnteriores, $datosNuevos, $request);

            return $resultado;
        });
    }

    public function registrar(
        string $accion,
        Model $modelo,
        array $datosAnteriores = [],
        array $datosNuevos = [],
        ?Request $request = null,
    ): void {
        $request ??= app()->bound('request') ? app(Request::class) : null;
        $usuario = $request?->user();

        // La bitácora sirve para rendición de cuentas del personal, no para
        // registrar solicitudes hechas por pacientes o integraciones externas.
        if (! $usuario || ! in_array($usuario->rol, [RolUsuario::Admin, RolUsuario::Asistente], true)) {
            return;
        }

        try {
            $conexionNegocio = $modelo->getConnection();
            $conexionAuditoria = (new LogAuditoria)->getConnection();

            if ($conexionNegocio !== $conexionAuditoria) {
                throw new LogicException(
                    'El modelo y la auditoría deben usar la misma conexión de base de datos.'
                );
            }

            if ($conexionNegocio->transactionLevel() < 1) {
                throw new LogicException(
                    'AuditoriaService::registrar requiere una transacción activa.'
                );
            }

            (new LogAuditoria)->newQuery()->create([
                'id_usuario' => $usuario->id,
                'actor_user_id' => $usuario->id,
                'actor_nombre' => trim($usuario->nombre.' '.$usuario->apellido),
                'actor_rol' => $usuario->rol->value,
                'accion' => $accion,
                'tabla' => $modelo->getTable(),
                'registro_id' => $modelo->getKey(),
                'datos_anteriores' => $datosAnteriores ?: null,
                'datos_nuevos' => $datosNuevos ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo escribir la auditoría de Dentissa', [
                'accion' => $accion,
                'tabla' => $modelo->getTable(),
                'registro_id' => $modelo->getKey(),
                'error' => $e->getMessage(),
            ]);

            // Los llamadores deben invocar este método dentro de la misma
            // transacción que modifica el registro de negocio. Al propagar
            // el error, Laravel revierte ambos cambios.
            throw AuditoriaNoDisponible::porError($e);
        }
    }

    private function snapshot(Model $modelo, array $campos): array
    {
        $datos = [];

        foreach ($campos as $campo) {
            $valor = $modelo->getAttribute($campo);

            if ($valor instanceof BackedEnum) {
                $valor = $valor->value;
            } elseif ($valor instanceof DateTimeInterface) {
                $valor = $valor->format(DATE_ATOM);
            }

            if (is_scalar($valor) || $valor === null || is_array($valor)) {
                $datos[$campo] = $valor;
            }
        }

        return $datos;
    }
}
