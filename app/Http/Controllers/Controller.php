<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    protected function paginationPageSize(Request $request): int
    {
        return in_array((string) $request->query('per_page', '20'), ['20', '50'], true)
            ? (int) $request->query('per_page', 20)
            : 20;
    }

    /**
     * Filtros de una lista (22/11/2026). Un valor inválido (?estado=xyz, una
     * búsqueda de más de 100 caracteres) se IGNORA y la lista se muestra sin ese
     * filtro. Antes se usaba $request->validate(), que en una petición GET
     * regresaba a la página anterior sin explicar nada.
     *
     * @param  array<string, mixed>  $reglas
     * @return array<string, mixed> solo los filtros válidos
     */
    protected function filtros(Request $request, array $reglas): array
    {
        return Validator::make($request->only(array_keys($reglas)), $reglas)->valid();
    }
}
