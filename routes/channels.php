<?php

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('users.{userId}', function (User $user, int $userId): bool {
    return $user->id === $userId
        && $user->activo
        && $user->puede('notificaciones');
});
