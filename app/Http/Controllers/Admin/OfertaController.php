<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoOferta;
use App\Http\Controllers\Controller;
use App\Models\Oferta;
use App\Models\Tratamiento;
use App\Rules\SinEnlaces;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

/**
 * Ofertas del panel (21/11/2026). Ninguna se publica al guardarla: queda
 * "En revisión" y el administrador la publica desde la lista después de
 * revisarla. Las ocultas siguen aquí para editarlas o volver a publicarlas.
 */
class OfertaController extends Controller
{
    private const CAMPOS_AUDITADOS = ['titulo', 'descripcion', 'id_tratamiento', 'tipo', 'valor_descuento', 'tipo_valor', 'precio_especial', 'fecha_inicio', 'fecha_fin', 'cupo_total', 'imagen', 'estado'];

    /** Lo que se ve en la portada: si cambia una oferta publicada, vuelve a revisión. */
    private const CAMPOS_PUBLICOS = ['titulo', 'descripcion', 'id_tratamiento', 'tipo', 'valor_descuento', 'tipo_valor', 'precio_especial', 'fecha_fin', 'cupo_total', 'imagen'];

    public function index(Request $request): View
    {
        return view('admin.ofertas.index', [
            // Primero las que esperan revisión.
            'ofertas' => Oferta::with(['tratamiento' => fn ($q) => $q->select('id', 'nombre', 'activo')->withCount(['servicios as servicios_activos' => fn ($s) => $s->where('activo', true)])])
                ->conLugaresUsados()
                ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'activa' THEN 1 ELSE 2 END")
                ->orderByDesc('fecha_inicio')
                ->paginate($this->paginationPageSize($request)),
        ]);
    }

    public function create(): View
    {
        return view('admin.ofertas.create', [
            'tratamientos' => $this->tratamientosElegibles(),
        ]);
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request) + [
            // Sin campo "Empieza": una oferta cuenta desde el día que se crea.
            'fecha_inicio' => now()->toDateString(),
            'estado' => EstadoOferta::Pendiente,
        ];

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('ofertas', 'public');
        }

        $auditoria->registrarCambio($request, 'oferta.creada', new Oferta, self::CAMPOS_AUDITADOS, fn () => Oferta::create($data));

        return Redirect::route('admin.ofertas.index')
            ->with('success', "Oferta «{$data['titulo']}» guardada En revisión. Revísala y publícala con ✓ para que se vea en la portada.");
    }

    public function edit(Oferta $oferta): View
    {
        return view('admin.ofertas.edit', [
            'oferta' => $oferta,
            'tratamientos' => $this->tratamientosElegibles($oferta),
        ]);
    }

    public function update(Request $request, Oferta $oferta, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request, $oferta);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('ofertas', 'public');
        }

        // Una oferta publicada cuyo contenido cambia vuelve a revisión: nada nuevo
        // llega a la portada sin que alguien lo publique.
        $oferta->fill($data);
        $volvioARevision = $oferta->estado === EstadoOferta::Activa && $oferta->isDirty(self::CAMPOS_PUBLICOS);
        if ($volvioARevision) {
            $oferta->estado = EstadoOferta::Pendiente;
        }
        $cambios = $oferta->getDirty();
        $oferta->refresh();

        $auditoria->registrarCambio($request, 'oferta.actualizada', $oferta, self::CAMPOS_AUDITADOS, fn () => $oferta->update($cambios));

        return Redirect::route('admin.ofertas.index')->with('success', $volvioARevision
            ? "Oferta «{$oferta->titulo}» guardada. Volvió a En revisión: publícala con ✓ para que se vea en la portada."
            : "Oferta «{$oferta->titulo}» guardada.");
    }

    public function publicar(Request $request, Oferta $oferta, AuditoriaService $auditoria): RedirectResponse
    {
        // Ligada a un tratamiento que ya no se agenda en línea: no se publica (no se podría usar).
        if ($oferta->id_tratamiento && ! Tratamiento::agendables()->whereKey($oferta->id_tratamiento)->exists()) {
            return Redirect::route('admin.ofertas.index')
                ->with('error', "La oferta «{$oferta->titulo}» es de un tratamiento inactivo. Actívalo en Catálogo o cambia el tratamiento antes de publicarla.");
        }

        if ($oferta->fecha_fin->lt(today())) {
            return Redirect::route('admin.ofertas.index')
                ->with('error', "La oferta «{$oferta->titulo}» ya venció. Cambia «Válida hasta» antes de publicarla.");
        }

        $auditoria->registrarCambio($request, 'oferta.publicada', $oferta, ['estado'], fn () => $oferta->update(['estado' => EstadoOferta::Activa]));

        // La portada muestra solo las que vencen primero (Oferta::MAXIMO_PORTADA):
        // el mensaje no promete lo que no pasa (22/11/2026).
        $enPortada = Oferta::paraPortada()->contains('id', $oferta->id);

        return Redirect::route('admin.ofertas.index')->with('success', $enPortada
            ? "Oferta «{$oferta->titulo}» publicada: ya se ve en la portada."
            : "Oferta «{$oferta->titulo}» publicada. La portada muestra las ".Oferta::MAXIMO_PORTADA.' que vencen primero; esta aparecerá cuando alguna termine o se oculte.');
    }

    public function ocultar(Request $request, Oferta $oferta, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio($request, 'oferta.ocultada', $oferta, ['estado'], fn () => $oferta->update(['estado' => EstadoOferta::Inactiva]));

        return Redirect::route('admin.ofertas.index')->with('success', "Oferta «{$oferta->titulo}» oculta: ya no se ve en la portada.");
    }

    public function destroy(Request $request, Oferta $oferta, AuditoriaService $auditoria): RedirectResponse
    {
        $auditoria->registrarCambio(
            $request,
            'oferta.eliminada',
            $oferta,
            ['titulo', 'estado'],
            fn () => $oferta->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.ofertas.index')->with('success', "Oferta «{$oferta->titulo}» eliminada.");
    }

    /**
     * Tratamientos que se pueden ligar a una oferta (21/11/2026): los que se
     * agendan en línea (activos y en una especialidad activa). Al editar se
     * incluye el que ya tenía aunque se haya desactivado, marcado como inactivo.
     */
    private function tratamientosElegibles(?Oferta $oferta = null)
    {
        $tratamientos = Tratamiento::agendables()->orderBy('nombre')->get(['id', 'nombre']);

        if ($oferta?->id_tratamiento && ! $tratamientos->contains('id', $oferta->id_tratamiento)) {
            $actual = Tratamiento::find($oferta->id_tratamiento, ['id', 'nombre']);
            if ($actual) {
                $actual->nombre .= ' (inactivo)';
                $tratamientos->push($actual);
            }
        }

        return $tratamientos;
    }

    private function validated(Request $request, ?Oferta $oferta = null): array
    {
        // "Beneficio para el paciente": Descuento (en % o $), Precio especial o Gratis,
        // más una cantidad (sin cantidad si es gratis); ver Oferta::BENEFICIOS y UNIDADES.
        $esPorcentaje = $request->input('beneficio') === 'descuento' && $request->input('unidad') !== 'monto';
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'min:5', 'max:120', new SinEnlaces],
            'descripcion' => ['required', 'string', 'min:20', 'max:300', new SinEnlaces],
            // Solo tratamientos que se agendan en línea; al editar se conserva el que ya tenía.
            'id_tratamiento' => ['nullable', Rule::in($this->tratamientosElegibles($oferta)->pluck('id')->all())],
            'beneficio' => ['required', Rule::in(array_keys(Oferta::BENEFICIOS))],
            // Descuento solo en % (21/11/2026); $ solo para conservar una oferta vieja que ya lo usaba.
            'unidad' => ['exclude_unless:beneficio,descuento', 'required', Rule::in($oferta?->beneficio() === 'monto' ? ['porcentaje', 'monto'] : ['porcentaje'])],
            'cantidad' => ['exclude_if:beneficio,gratis', 'required', 'numeric', 'min:1',
                // 100 % es "Gratis": así la portada dice Gratis y no "−100%" (22/11/2026).
                ...($esPorcentaje ? ['max:99'] : ['max:999999'])],
            'fecha_fin' => ['required', 'date', 'after_or_equal:today'],
            'cupo_total' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ], [
            'beneficio.required' => 'Elige el beneficio para el paciente.',
            'cantidad.max' => $esPorcentaje ? 'Un descuento de 100 % es gratis: elige «Gratis».' : 'La cantidad es demasiado grande.',
            'id_tratamiento.in' => 'Ese tratamiento ya no se agenda en línea (está inactivo). Elige otro o actívalo en Catálogo.',
            'fecha_fin.after_or_equal' => '«Válida hasta» no puede ser una fecha pasada.',
        ], [
            'titulo' => 'título',
            'descripcion' => 'descripción',
            'beneficio' => 'beneficio para el paciente',
            'unidad' => 'unidad del descuento',
            'cantidad' => $esPorcentaje ? 'porcentaje' : 'cantidad',
            'cupo_total' => 'lugares disponibles',
        ]);

        $clave = Oferta::claveDeFormulario($datos['beneficio'], $datos['unidad'] ?? null);
        $columnas = Oferta::columnasDeBeneficio($clave, isset($datos['cantidad']) ? (float) $datos['cantidad'] : null);
        unset($datos['beneficio'], $datos['unidad'], $datos['cantidad'], $datos['imagen']);

        // Vacío = sin límite (la columna guarda 0).
        $datos['cupo_total'] = (int) ($datos['cupo_total'] ?? 0);

        return $columnas + $datos;
    }
}
