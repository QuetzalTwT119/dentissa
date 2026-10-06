<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use Illuminate\Contracts\View\View;

class OfertaController extends Controller
{
    public function index(): View
    {
        $hoy = now()->toDateString();

        $ofertas = Oferta::with('tratamiento:id,nombre')
            ->where('activa', true)
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->orderBy('fecha_fin')
            ->get();

        return view('ofertas.index', [
            'ofertas' => $ofertas,
        ]);
    }
}
