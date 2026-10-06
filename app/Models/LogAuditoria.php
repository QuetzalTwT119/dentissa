<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id_usuario', 'accion', 'tabla', 'registro_id', 'datos_anteriores', 'datos_nuevos', 'ip', 'user_agent', 'fecha'])]
class LogAuditoria extends Model
{
    use HasFactory;

    protected $table = 'logs_auditoria';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'datos_anteriores' => 'array',
            'datos_nuevos' => 'array',
            'fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
