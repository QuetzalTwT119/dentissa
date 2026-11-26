<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
    /**
     * Datos y foto en un solo "Guardar" (23/11/2026). La foto es opcional;
     * "quitar_foto" la borra. Cada cambio queda en Control de actividades.
     */
    public function update(ProfileUpdateRequest $request, AuditoriaService $auditoria): RedirectResponse
    {
        $user = $request->user();
        $auditoria->registrarCambio(
            $request,
            'perfil.actualizado',
            $user,
            ['nombre', 'apellido', 'email', 'telefono', 'especialidad'],
            function () use ($request, $user) {
                $user->fill($request->safe()->except(['foto_perfil', 'quitar_foto']));

                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }

                $user->save();

                return $user;
            },
        );

        if ($request->hasFile('foto_perfil')) {
            $this->guardarFoto($request, $user, $auditoria);
        } elseif ($request->boolean('quitar_foto')) {
            $this->quitarFoto($request, $user, $auditoria);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->foto_perfil;

        abort_unless($this->isManagedPhoto($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function guardarFoto(Request $request, User $user, AuditoriaService $auditoria): void
    {
        $oldPath = $user->foto_perfil;
        $newPath = $request->file('foto_perfil')->store('profile-photos', 'local');

        try {
            $auditoria->registrarCambio(
                $request,
                'perfil.foto_actualizada',
                $user,
                ['foto_perfil'],
                function () use ($user, $newPath) {
                    $user->foto_perfil = $newPath;
                    $user->save();

                    return $user;
                },
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        $this->deleteManagedPhoto($oldPath);
    }

    private function quitarFoto(Request $request, User $user, AuditoriaService $auditoria): void
    {
        $oldPath = $user->foto_perfil;

        if (! $oldPath) {
            return;
        }

        $auditoria->registrarCambio(
            $request,
            'perfil.foto_eliminada',
            $user,
            ['foto_perfil'],
            function () use ($user) {
                $user->foto_perfil = null;
                $user->save();

                return $user;
            },
        );

        $this->deleteManagedPhoto($oldPath);
    }

    private function isManagedPhoto(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'profile-photos/')
            && str_contains($path, '..') === false;
    }

    private function deleteManagedPhoto(?string $path): void
    {
        if ($this->isManagedPhoto($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
