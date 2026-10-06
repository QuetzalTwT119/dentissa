<?php

namespace App\Http\Controllers;

use App\Enums\TipoImagen;
use App\Models\Imagen;
use Illuminate\Contracts\View\View;

class GaleriaController extends Controller
{
    public function index(): View
    {
        $imagenes = Imagen::where('tipo', TipoImagen::Galeria)
            ->where('activa', true)
            ->orderBy('orden')
            ->get();

        return view('galeria.index', [
            'imagenes' => $imagenes,
        ]);
    }
}
