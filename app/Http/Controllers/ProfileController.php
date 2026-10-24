<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
