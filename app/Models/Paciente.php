<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id_usuario', 'nombre', 'apellido', 'telefono', 'email',
    'fecha_nacimiento', 'direccion', 'ciudad', 'ocupacion',
    'alergias', 'medicamentos_actuales', 'observaciones_generales', 'tipo_sangre',
    'seguro_medico', 'numero_seguro', 'contacto_emergencia', 'telefono_emergencia',
    'curp', 'rfc', 'ine_clave', 'tutor_id',
])]
class Paciente extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->nombre} {$this->apellido}"));
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'tutor_id');
    }

    public function dependientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'tutor_id');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }

    public function testimonios(): HasMany
    {
        return $this->hasMany(Testimonio::class, 'id_paciente');
    }

    public function historialClinico(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_paciente');
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'id_paciente');
    }
}
