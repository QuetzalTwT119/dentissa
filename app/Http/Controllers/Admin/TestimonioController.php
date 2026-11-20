<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoTestimonio;
use App\Http\Controllers\Controller;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class TestimonioController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'estado' => ['nullable', Rule::enum(EstadoTestimonio::class)],
        ]);

        $testimonios = Testimonio::with('paciente:id,nombre,apellido')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $validated['estado']))
            ->orderByDesc('created_at')
            ->paginate($this->paginationPageSize($request))
            ->withQueryString();

        return view('admin.testimonios.index', [
            'testimonios' => $testimonios,
            'filtros' => $request->only('estado'),
        ]);
    }

    public function create(): View
    {
        return view('admin.testimonios.create', [
            'pacientes' => $this->pacientes(),
        ]);
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['estado'] === EstadoTestimonio::Aprobado->value && empty($data['fecha_publicacion'])) {
            $data['fecha_publicacion'] = now();
        }

        $auditoria->registrarCambio(
            $request,
            'testimonio.creado',
            new Testimonio,
            ['id_paciente', 'calificacion', 'estado', 'fecha_publicacion'],
            fn () => Testimonio::create($data),
        );

        return Redirect::route('admin.testimonios.index')->with('success', 'Testimonio creado.');
    }

    public function edit(Testimonio $testimonio): View
    {
        return view('admin.testimonios.edit', [
            'testimonio' => $testimonio,
            'pacientes' => $this->pacientes(),
        ]);
    }

    public function update(Request $request, Testimonio $testimonio, AuditoriaService $auditoria): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['estado'] === EstadoTestimonio::Aprobado->value && empty($data['fecha_publicacion']) && $testimonio->estado !== EstadoTestimonio::Aprobado) {
            $data['fecha_publicacion'] = now();
        }

        $auditoria->registrarCambio(
            $request,
            'testimonio.actualizado',
            $testimonio,
            ['id_paciente', 'calificacion', 'estado', 'fecha_publicacion'],
            fn () => $testimonio->update($data),
        );

        return Redirect::route('admin.testimonios.index')->with('success', 'Testimonio guardado.');
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

    private function pacientes()
    {
        return Paciente::select('id', 'nombre', 'apellido')->orderBy('nombre')->get()->map(fn (Paciente $p) => [
            'id' => $p->id,
            'nombre' => $p->nombre_completo,
        ])->values();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'id_paciente' => ['required', 'exists:pacientes,id'],
            'calificacion' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comentario' => ['required', 'string'],
            'estado' => ['required', Rule::enum(EstadoTestimonio::class)],
        ]);
    }
}
