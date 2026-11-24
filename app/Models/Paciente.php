<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        // aviso_privacidad_* no está en Fillable a propósito: solo se escribe
        // con forceFill desde CrearCitaService, nunca desde un formulario.
        return [
            'fecha_nacimiento' => 'date',
            'aviso_privacidad_aceptado_en' => 'datetime',
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

    /** Cuestionario de historia clínica (una por paciente, 22/11/2026). */
    /** Pagos registrados (24/11/2026). */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_paciente');
    }

    /** Consentimientos informados impresos (24/11/2026). */
    public function consentimientos(): HasMany
    {
        return $this->hasMany(Consentimiento::class, 'id_paciente');
    }

    public function historiaClinica(): HasOne
    {
        return $this->hasOne(HistoriaClinica::class, 'id_paciente');
    }

    /** Exploraciones clínicas con odontograma (23/11/2026); la más reciente primero. */
    public function exploracionesClinicas(): HasMany
    {
        return $this->hasMany(ExploracionClinica::class, 'id_paciente')->latest('fecha')->latest('id');
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
