<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class OfertaController extends Controller
{
    public function index(Request $request): View
    {
        $ofertas = Oferta::with('tratamiento:id,nombre')
            ->publicadas()
            ->orderBy('fecha_fin')
            ->paginate($this->paginationPageSize($request));

        return view('ofertas.index', [
            'ofertas' => $ofertas,
        ]);
    }
}
