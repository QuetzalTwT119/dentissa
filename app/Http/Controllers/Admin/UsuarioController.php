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
        $validated = $request->validate([
            'rol' => ['nullable', Rule::enum(RolUsuario::class)],
            'activo' => ['nullable', 'in:0,1'],
        ]);

        $usuarios = User::when($request->filled('rol'), fn ($q) => $q->where('rol', $validated['rol']))
            ->when($request->filled('activo'), fn ($q) => $q->where('activo', $validated['activo']))
            ->orderBy('nombre')
            ->get();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'filtros' => $request->only(['rol', 'activo']),
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

        return Redirect::route('admin.usuarios.index');
    }

    public function edit(User $usuario): View
    {
        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
        ]);
    }

    public function update(Request $request, User $usuario, AuditoriaService $auditoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'telefono' => ['required', 'string', 'max:20'],
            'rol' => ['required', Rule::enum(RolUsuario::class)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $auditoria->registrarCambio(
            $request,
            'usuario.actualizado',
            $usuario,
            ['nombre', 'apellido', 'email', 'telefono', 'rol', 'activo'],
            function () use ($usuario, $validated) {
                $usuario->fill([
                    'nombre' => $validated['nombre'],
                    'apellido' => $validated['apellido'],
                    'email' => $validated['email'],
                    'telefono' => $validated['telefono'],
                    'rol' => $validated['rol'],
                ]);

                if (! empty($validated['password'])) {
                    $usuario->password = Hash::make($validated['password']);
                }

                $usuario->save();

                return $usuario;
            },
        );

        return Redirect::route('admin.usuarios.index');
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

        return Redirect::route('admin.usuarios.index');
    }
}
