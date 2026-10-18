<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'evento', 'titulo', 'mensaje', 'url', 'leida_en'])]
class NotificacionSistema extends Model
{
    protected $table = 'notificaciones_sistema';

    protected function casts(): array
    {
        return [
            'leida_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
