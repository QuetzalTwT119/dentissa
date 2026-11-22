<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCita;
use App\Http\Controllers\Concerns\AlternaEstado;
use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    use AlternaEstado;

    public function index(Request $request): View
    {
        // Filtro global (22/11/2026), igual que Tratamientos. Antes no lo tenía
        // por ser pocos y ordenarse a mano; ahora el orden se respeta aparte.
        $filtros = $this->filtros($request, [
            'buscar' => ['nullable', 'string', 'max:100'],
            'activo' => ['nullable', 'in:1,0'],
        ]);

        // Posición REAL en el carrusel (no la de la lista filtrada).
        $orden = Servicio::orderBy('orden')->orderBy('id')->pluck('id');

        return view('admin.servicios.index', [
            // citas_activas: con alguna, no se puede eliminar (22/11/2026, la lista lo dice).
            'servicios' => Servicio::withCount(['tratamientos', 'citas as citas_activas' => fn ($c) => $c->whereNotIn('estado', [\App\Enums\EstadoCita::Cancelada, \App\Enums\EstadoCita::Completada, \App\Enums\EstadoCita::NoAsistio])])
                ->when($filtros['buscar'] ?? null, fn ($q, $texto) => $q->whereContiene('nombre', $texto))
                ->when(isset($filtros['activo']), fn ($q) => $q->where('activo', $filtros['activo'] === '1'))
                ->orderBy('orden')->orderBy('id')
                ->paginate($this->paginationPageSize($request))
                ->withQueryString(),
            'posiciones' => $orden->flip()->map(fn ($i) => $i + 1)->all(),
            'totalServicios' => $orden->count(),
            'filtrando' => filled($filtros['buscar'] ?? null) || isset($filtros['activo']),
        ]);
    }

    public function create(): View
    {
        // Mismo formulario que Editar (22/11/2026): ya se eligen foto y tratamientos al crear.
        return view('admin.servicios.create', [
            'servicio' => new Servicio(['activo' => true]),
            'tratamientos' => $this->tratamientosAgendables(),
            'tratamientosAsignados' => collect(),
        ]);
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request);
        // El orden ya no se escribe a mano: el servicio nuevo va al final del
        // carrusel y se acomoda con las flechas ↑ ↓ de la lista.
        $data['orden'] = (int) Servicio::max('orden') + 1;
        // Nace disponible; se oculta con el interruptor de la lista.
        $data['activo'] = true;

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        }

        $servicio = $auditoria->registrarCambio(
            $request,
            'servicio.creado',
            new Servicio,
            ['nombre', 'descripcion', 'imagen', 'activo', 'orden'],
            fn () => Servicio::create($data),
        );

        if ($request->boolean('tratamientos_enviados')) {
            $this->sincronizarTratamientos($request, $servicio, $auditoria);
        }

        $sinTratamientos = $servicio->tratamientos()->doesntExist();

        return Redirect::route('admin.servicios.index')->with('success', "Servicio «{$servicio->nombre}» creado."
            .($sinTratamientos ? ' Sin tratamientos, la portada lo muestra como "Agenda por teléfono".' : ''));
    }

    public function edit(Servicio $servicio): View
    {
        return view('admin.servicios.edit', [
            'servicio' => $servicio,
            'tratamientos' => $this->tratamientosAgendables($servicio),
            'tratamientosAsignados' => $servicio->tratamientos()->where('tratamientos.activo', true)->pluck('tratamientos.id'),
        ]);
    }

    /**
     * Solo lo que se puede agendar. Los inactivos se administran en
     * Catálogo → Tratamientos; su vínculo se conserva (ver sincronizarTratamientos).
     * Con cada uno, en qué OTROS servicios está ("También en …").
     */
    private function tratamientosAgendables(?Servicio $servicio = null)
    {
        return Tratamiento::where('activo', true)
            ->with(['servicios' => fn ($q) => $q->select('servicios.id', 'servicios.nombre')
                ->when($servicio?->exists, fn ($q) => $q->where('servicios.id', '!=', $servicio->id))
                ->orderBy('servicios.orden')])
            ->orderBy('nombre')
            ->get(['id', 'nombre']);
    }

    public function update(Request $request, Servicio $servicio, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request, $servicio->id);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('servicios', 'public');
        } else {
            unset($data['imagen']);
        }

        $auditoria->registrarCambio(
            $request,
            'servicio.actualizado',
            $servicio,
            ['nombre', 'descripcion', 'icono', 'imagen', 'activo', 'orden'],
            fn () => $servicio->update($data),
        );

        // La pantalla de edición manda datos, foto y tratamientos en un solo
        // formulario: así elegir una foto y guardar nunca pierde nada.
        if ($request->boolean('tratamientos_enviados')) {
            $this->sincronizarTratamientos($request, $servicio, $auditoria);
        }

        return Redirect::route('admin.servicios.index')->with('success', "Servicio «{$servicio->nombre}» guardado.");
    }

    public function syncTratamientos(Request $request, Servicio $servicio, AuditoriaService $auditoria): RedirectResponse
    {
        $this->sincronizarTratamientos($request, $servicio, $auditoria);

        return Redirect::route('admin.servicios.edit', $servicio)->with('success', 'Tratamientos del servicio guardados.');
    }

    private function sincronizarTratamientos(Request $request, Servicio $servicio, AuditoriaService $auditoria): void
    {
        $validated = $request->validate([
            'tratamiento_ids' => ['array'],
            // Solo tratamientos activos: uno inactivo no se puede agendar.
            'tratamiento_ids.*' => ['integer', Rule::exists('tratamientos', 'id')->where('activo', true)],
        ], [
            'tratamiento_ids.*.exists' => 'Ese tratamiento está inactivo; actívalo primero en Catálogo → Tratamientos.',
        ]);

        DB::transaction(function () use ($request, $servicio, $auditoria, $validated) {
            $anteriores = $servicio->tratamientos()->pluck('tratamientos.id')->all();
            // Los inactivos no se muestran en la pantalla, así que no llegan en la
            // petición: se conservan para que reactivarlos los devuelva a este servicio.
            $inactivos = $servicio->tratamientos()->where('tratamientos.activo', false)->pluck('tratamientos.id')->all();
            $nuevos = array_values(array_unique([...array_map('intval', $validated['tratamiento_ids'] ?? []), ...$inactivos]));
            $servicio->tratamientos()->sync($nuevos);
            $auditoria->registrar('servicio.tratamientos_actualizados', $servicio, [
                'tratamiento_ids' => $anteriores,
            ], [
                'tratamiento_ids' => $nuevos,
            ], $request);
        });
    }

    /**
     * Sube o baja un servicio un lugar en el carrusel intercambiándolo con su
     * vecino. Antes renumera 1..n para que órdenes repetidos (0, 0, 1…) no
     * dejen el movimiento sin efecto.
     */
    public function mover(Request $request, Servicio $servicio, string $direccion, AuditoriaService $auditoria): RedirectResponse
    {
        DB::transaction(function () use ($request, $servicio, $direccion, $auditoria) {
            $lista = Servicio::orderBy('orden')->orderBy('id')->lockForUpdate()->get(['id', 'orden']);
            $ids = $lista->pluck('id')->values();
            $posicion = $ids->search($servicio->id);
            $vecina = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;

            if ($posicion === false || $vecina < 0 || $vecina >= $ids->count()) {
                return;
            }

            $ids = $ids->all();
            [$ids[$posicion], $ids[$vecina]] = [$ids[$vecina], $ids[$posicion]];
            $anterior = $servicio->orden;

            foreach ($ids as $indice => $id) {
                Servicio::whereKey($id)->update(['orden' => $indice + 1]);
            }

            $auditoria->registrar('servicio.reordenado', $servicio, ['orden' => $anterior], ['orden' => $vecina + 1], $request);
        });

        return Redirect::back()->with('success', "«{$servicio->nombre}» se movió ".($direccion === 'arriba' ? 'hacia arriba.' : 'hacia abajo.'));
    }

    /** Interruptor de la lista (x-admin.interruptor-estado). */
    public function estado(Request $request, Servicio $servicio): RedirectResponse
    {
        return $this->alternarCampo($request, $servicio, 'activo', 'servicio.estado_cambiado', "«{$servicio->nombre}» está disponible: se muestra en la portada y se puede agendar.", "«{$servicio->nombre}» ya no está disponible: no se muestra en la portada ni se puede agendar.");
    }

    public function destroy(Request $request, Servicio $servicio, AuditoriaService $auditoria): RedirectResponse
    {
        $tieneCitasActivas = $servicio->citas()
            ->whereNotIn('estado', [EstadoCita::Cancelada, EstadoCita::Completada, EstadoCita::NoAsistio])
            ->exists();

        if ($tieneCitasActivas) {
            return Redirect::route('admin.servicios.index')
                ->with('error', 'No se puede eliminar el servicio: tiene citas activas asociadas.');
        }

        $auditoria->registrarCambio(
            $request,
            'servicio.eliminado',
            $servicio,
            ['nombre', 'activo'],
            fn () => $servicio->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.servicios.index')->with('success', "Servicio «{$servicio->nombre}» eliminado.");
    }

    /**
     * Nombre, descripción y foto. "Disponible" (activo) NO se cambia desde el
     * formulario (22/11/2026): solo con el interruptor de la lista (estado()).
     * Así guardar en Editar nunca oculta ni publica un servicio sin querer.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('servicios', 'nombre')->ignore($ignoreId)],
            'descripcion' => ['nullable', 'string'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }
}
