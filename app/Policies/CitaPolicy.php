<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\User;

class CitaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function create(User $user): bool
    {
        // La creación interna es una capacidad del personal. La agenda
        // pública usa CitaPublicaController y no depende de esta policy.
        return $this->esPersonal($user);
    }

    public function update(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function cancel(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function confirm(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function complete(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function start(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    public function noShow(User $user, Cita $cita): bool
    {
        return $this->esPersonal($user);
    }

    private function esPersonal(User $user): bool
    {
        return in_array($user->rol, [RolUsuario::Asistente, RolUsuario::Admin], true);
    }
}
