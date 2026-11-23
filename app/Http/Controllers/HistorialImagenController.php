<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Enums\TipoImagen;
use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistorialImagenController extends Controller
{
    /**
     * Sirve un adjunto del historial clínico solo al admin — a diferencia de las
     * demás imágenes del sistema (servicios, tratamientos, ofertas, galería),
     * estos archivos son datos clínicos privados y no deben quedar accesibles
     * vía URL pública sin autenticación.
     */
    public function show(Request $request, Imagen $imagen): StreamedResponse
    {
        abort_unless($imagen->tipo === TipoImagen::HistorialClinico, 404);
        abort_unless($request->user()->puede('pacientes'), 403);

        abort_unless(Storage::disk('local')->exists($imagen->url), 404);

        return Storage::disk('local')->response($imagen->url);
    }
}
