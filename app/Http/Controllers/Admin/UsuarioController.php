<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * No expone destroy(): historial_clinico.id_doctor referencia a users con
 * cascadeOnDelete, así que un borrado real arrastraría el historial clínico
 * registrado por ese usuario. La baja soportada es desactivar la cuenta
 * (ver toggleActivo), que conserva el expediente y el rastro de auditoría.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $this->filtros($request, [
            'rol' => ['nullable', Rule::enum(RolUsuario::class)],
            'activo' => ['nullable', 'in:0,1'],
        ]);

        $usuarios = User::withCount('consultasRegistradas')
            ->when(filled($validated['rol'] ?? null), fn ($q) => $q->where('rol', $validated['rol']))
            ->when(filled($validated['activo'] ?? null), fn ($q) => $q->where('activo', $validated['activo']))
            ->orderBy('nombre')
            ->paginate($this->paginationPageSize($request))
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'filtros' => $validated,
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'telefono' => ['required', 'string', 'max:20'],
            'rol' => ['required', Rule::enum(RolUsuario::class)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $auditoria->registrarCambio(
            $request,
            'usuario.creado',
            new User,
            ['nombre', 'apellido', 'email', 'telefono', 'rol', 'activo'],
            fn () => User::create([
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'],
                'rol' => $validated['rol'],
                'password' => Hash::make($validated['password']),
            ]),
        );

        return Redirect::route('admin.usuarios.index')->with('success', "Usuario {$request->input('nombre')} {$request->input('apellido')} creado.");
    }

    /**
     * Eliminar (22/11/2026). Nunca la cuenta propia, y nunca a quien registró
     * consultas: se borrarían en cascada del historial de los pacientes; para
     * esas cuentas está "Desactivar". La bitácora conserva su nombre.
     */
    public function destroy(Request $request, User $usuario, AuditoriaService $auditoria): RedirectResponse
    {
        abort_if($usuario->is($request->user()), 422, 'No puedes eliminar tu propia cuenta.');

        $consultas = $usuario->consultasRegistradas()->count();
        if ($consultas > 0) {
            return Redirect::route('admin.usuarios.index')->with('error', "No se puede eliminar a {$usuario->nombre} {$usuario->apellido}: registró {$consultas} ".($consultas === 1 ? 'consulta clínica' : 'consultas clínicas').'. Desactívalo para conservarlas.');
        }

        $nombre = trim($usuario->nombre.' '.$usuario->apellido);
        $auditoria->registrarCambio(
            $request,
            'usuario.eliminado',
            $usuario,
            ['nombre', 'apellido', 'email', 'rol'],
            fn () => $usuario->delete(),
            eliminado: true,
        );

        return Redirect::route('admin.usuarios.index')->with('success', "Usuario {$nombre} eliminado.");
    }

    public function toggleActivo(Request $request, User $usuario, AuditoriaService $auditoria): RedirectResponse
    {
        abort_if($usuario->id === $request->user()->id, 422, 'No puedes desactivar tu propia cuenta.');

        $auditoria->registrarCambio(
            $request,
            $usuario->activo ? 'usuario.desactivado' : 'usuario.activado',
            $usuario,
            ['activo'],
            fn () => $usuario->update(['activo' => ! $usuario->activo]),
        );

        return Redirect::route('admin.usuarios.index')->with('success', $usuario->fresh()->activo ? "{$usuario->nombre} {$usuario->apellido} está activo." : "{$usuario->nombre} {$usuario->apellido} está desactivado: ya no puede iniciar sesión.");
    }
}
