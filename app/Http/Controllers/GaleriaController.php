<?php

namespace App\Http\Controllers;

use App\Enums\TipoImagen;
use App\Models\Imagen;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GaleriaController extends Controller
{
    public function index(Request $request): View
    {
        $imagenes = Imagen::where('tipo', TipoImagen::Galeria)
            ->where('activa', true)
            ->orderBy('orden')
            ->paginate($this->paginationPageSize($request));

        return view('galeria.index', [
            'imagenes' => $imagenes,
        ]);
    }
}
