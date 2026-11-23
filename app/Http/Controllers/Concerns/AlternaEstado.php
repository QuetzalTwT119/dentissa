<?php

namespace App\Http\Controllers\Concerns;

use App\Services\AuditoriaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Interruptor global de las listas (x-admin.interruptor-estado, 22/11/2026):
 * enciende o apaga un registro, lo deja en Control de actividades y regresa a
 * la lista (con sus filtros) diciendo qué cambió.
 */
trait AlternaEstado
{
    protected function alternarCampo(
        Request $request,
        Model $modelo,
        string $campo,
        string $accion,
        string $siActivo,
        string $siInactivo,
    ): RedirectResponse {
        app(AuditoriaService::class)->registrarCambio(
            $request,
            $accion,
            $modelo,
            [$campo],
            fn () => $modelo->update([$campo => ! $modelo->{$campo}]),
        );

        return Redirect::back()->with('success', $modelo->{$campo} ? $siActivo : $siInactivo);
    }
}
