<?php

namespace App\Http\Controllers;

use App\Models\Oferta;
use App\Models\Servicio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Página pública con todas las promociones vigentes (25/11/2026).
 *
 * La portada muestra como máximo Oferta::MAXIMO_PORTADA en carrusel; aquí el
 * paciente ve todas en cuadrícula y puede filtrarlas por especialidad. Usa las
 * mismas reglas que la portada (Oferta::vigentesConLugar): publicadas, dentro
 * de sus fechas, con lugar y agendables.
 */
class PromocionPublicaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $ofertas = Oferta::vigentesConLugar();
        $ofertas->load('tratamiento.servicios:id,nombre,activo');

        // Solo las especialidades que tienen alguna promoción: un filtro sin
        // resultados confunde.
        $especialidades = Servicio::where('activo', true)
            ->whereIn('id', $ofertas->flatMap(fn (Oferta $oferta) => $oferta->tratamiento?->servicios->where('activo', true)->pluck('id') ?? [])->unique())
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        $elegida = $especialidades->firstWhere('id', (int) $request->query('especialidad'));

        if ($elegida) {
            $ofertas = $ofertas->filter(fn (Oferta $oferta) => $oferta->tratamiento?->servicios->contains('id', $elegida->id))->values();
        }

        return view('promociones.index', [
            'ofertas' => $ofertas,
            'especialidades' => $especialidades,
            'elegida' => $elegida,
            'contacto' => config('dentissa.contacto'),
        ]);
    }
}
