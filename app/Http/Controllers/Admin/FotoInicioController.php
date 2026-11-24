<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DatosConsultorio;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

/**
 * Foto grande del inicio de la portada (25/11/2026). La de fábrica es de
 * catálogo (no es la doctora ni su consultorio): desde Contenido → Galería se
 * sube una foto real que la reemplaza, y "Quitar" vuelve a la de fábrica.
 */
class FotoInicioController extends Controller
{
    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validate([
            'foto_inicio' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto_inicio.required' => 'Elige una foto.',
            'foto_inicio.image' => 'El archivo debe ser una foto (JPG, PNG o WebP).',
            'foto_inicio.mimes' => 'El archivo debe ser una foto (JPG, PNG o WebP).',
            'foto_inicio.max' => 'La foto pesa más de 5 MB. Prueba con una más ligera.',
        ]);

        $fila = DatosConsultorio::fila();
        $anterior = $fila->foto_inicio;
        $nueva = $request->file('foto_inicio')->store('portada', 'public');

        DB::transaction(function () use ($fila, $nueva, $anterior, $request, $auditoria) {
            $fila->fill(['foto_inicio' => $nueva])->save();
            $auditoria->registrar('portada.foto_inicio_cambiada', $fila, ['foto_inicio' => $anterior], ['foto_inicio' => $nueva], $request);
        });

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return Redirect::to(route('admin.galeria.index').'#foto-inicio')
            ->with('success', 'Foto de inicio guardada. Ya se ve en la portada.');
    }

    public function destroy(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $fila = DatosConsultorio::fila();
        $anterior = $fila->foto_inicio;

        if ($anterior) {
            DB::transaction(function () use ($fila, $anterior, $request, $auditoria) {
                $fila->fill(['foto_inicio' => null])->save();
                $auditoria->registrar('portada.foto_inicio_quitada', $fila, ['foto_inicio' => $anterior], request: $request);
            });
            Storage::disk('public')->delete($anterior);
        }

        return Redirect::to(route('admin.galeria.index').'#foto-inicio')
            ->with('success', 'Se quitó la foto de inicio. La portada vuelve a la de fábrica.');
    }
}
