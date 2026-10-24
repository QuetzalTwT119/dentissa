<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Enums\RolUsuario;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $user = $request->user();
        $auditoria->registrarCambio(
            $request,
            'perfil.actualizado',
            $user,
            ['nombre', 'apellido', 'email', 'telefono'],
            function () use ($request, $user) {
                $user->fill($request->validated());

                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }

                $user->save();

                return $user;
            },
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // El borrado físico de users puede eliminar pacientes e historiales
        // clínicos por las claves foráneas con cascadeOnDelete(). La baja debe
        // ser reversible y conservar la trazabilidad de la clínica. El bloqueo
        // de filas mantiene atómico el límite de la última cuenta admin.
        $desactivada = DB::transaction(function () use ($request, $auditoria, $user) {
            if ($user->rol === RolUsuario::Admin) {
                $administradoresActivos = User::query()
                    ->where('rol', RolUsuario::Admin)
                    ->lockForUpdate()
                    ->get(['id', 'activo'])
                    ->where('activo', true)
                    ->count();

                if ($administradoresActivos <= 1) {
                    return false;
                }
            }

            $auditoria->registrarCambio(
                $request,
                'usuario.desactivado_por_titular',
                $user,
                ['activo'],
                fn () => $user->update(['activo' => false]),
            );

            return true;
        });

        if (! $desactivada) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'No puedes desactivar la única cuenta administradora activa. Activa otra cuenta admin primero.',
            ], 'userDeletion');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', 'account-deactivated');
    }
}
