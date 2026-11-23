<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoTestimonio;
use App\Http\Controllers\Controller;
use App\Models\Testimonio;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class TestimonioController extends Controller
{
    /*
     * La clínica modera: aprueba, rechaza o elimina. No crea ni edita testimonios:
     * escribirlos o cambiarlos a nombre de un paciente serían reseñas falsas
     * (decisión del 20/11/2026). Los escribe el paciente en la portada.
     */
    public function index(Request $request): View
    {
        $validated = $this->filtros($request, [
            'buscar' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::enum(EstadoTestimonio::class)],
        ]);

        // Buscador global (QA 22/11/2026): por paciente o por lo que escribió.
        $testimonios = Testimonio::with('paciente:id,nombre,apellido')
            ->when(filled($validated['buscar'] ?? null), fn ($q) => $q->where(fn ($q) => $q
                ->whereContiene('comentario', trim($validated['buscar']))
                // "María Prueba": cada palabra en el nombre o en el apellido
                // (sin CONCAT, que no existe igual en MySQL y en SQLite).
                ->orWhereHas('paciente', function ($p) use ($validated) {
                    foreach (preg_split('/\s+/', trim($validated['buscar'])) as $palabra) {
                        $p->where(fn ($q) => $q->whereContiene('nombre', $palabra)->orWhereContiene('apellido', $palabra));
                    }
                })))
            ->when(filled($validated['estado'] ?? null), fn ($q) => $q->where('estado', $validated['estado']))
            ->orderByDesc('created_at')
            ->paginate($this->paginationPageSize($request))
            ->withQueryString();

        return view('admin.testimonios.index', [
            'testimonios' => $testimonios,
            'filtros' => $validated,
        ]);
    }

    public function aprobar(Request $request, Testimonio $testimonio, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'testimonio.aprobado',
            $testimonio,
            ['estado', 'fecha_publicacion'],
            fn () => $testimonio->update([
                'estado' => EstadoTestimonio::Aprobado,
                'fecha_publicacion' => now(),
            ]),
        );

        return Redirect::route('admin.testimonios.index')->with('success', 'Testimonio aprobado: ya se ve en la portada.');
    }

    public function rechazar(Request $request, Testimonio $testimonio, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'testimonio.rechazado',
            $testimonio,
            ['estado'],
            fn () => $testimonio->update(['estado' => EstadoTestimonio::Rechazado]),
        );

        return Redirect::route('admin.testimonios.index')->with('success', 'Testimonio rechazado: no se mostrará en la portada.');
    }

    public function destroy(Request $request, Testimonio $testimonio, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'testimonio.eliminado',
            $testimonio,
            ['id_paciente', 'calificacion', 'estado'],
            fn () => $testimonio->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.testimonios.index')->with('success', 'Testimonio eliminado.');
    }

}
