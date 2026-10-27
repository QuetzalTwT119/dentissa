<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
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

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->foto_perfil;

        abort_unless($this->isManagedPhoto($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function updatePhoto(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $request->validateWithBag('profilePhoto', [
            'foto_perfil' => [
                'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
                'dimensions:max_width=3000,max_height=3000',
            ],
        ]);

        $user = $request->user();
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

        return Redirect::route('profile.edit')->with('status', 'profile-photo-updated');
    }

    public function deletePhoto(Request $request, AuditoriaService $auditoria): RedirectResponse
    {
        $user = $request->user();
        $oldPath = $user->foto_perfil;

        if ($oldPath) {
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

        return Redirect::route('profile.edit')->with('status', 'profile-photo-deleted');
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
