<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RolUsuario;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Los campos dos_pasos_* no están en Fillable: solo los escribe
// DosPasosService con forceFill, nunca un formulario.
#[Fillable(['nombre', 'apellido', 'email', 'telefono', 'cedula_profesional', 'especialidad', 'password', 'rol', 'foto_perfil', 'activo'])]
#[Hidden(['password', 'remember_token', 'dos_pasos_secreto', 'dos_pasos_recuperacion'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'password' => 'hashed',
            'rol' => RolUsuario::class,
            'activo' => 'boolean',
            'telefono_verificado' => 'boolean',
            // Cifrados con APP_KEY: un volcado de la base no revela el secreto.
            'dos_pasos_secreto' => 'encrypted',
            'dos_pasos_recuperacion' => 'encrypted:array',
            'dos_pasos_confirmado_en' => 'datetime',
        ];
    }

    public function tieneDosPasos(): bool
    {
        return $this->dos_pasos_confirmado_en !== null && filled($this->dos_pasos_secreto);
    }

    /** ¿Su rol tiene este módulo? (config/permisos.php). */
    public function puede(string $modulo): bool
    {
        return $this->rol instanceof RolUsuario && $this->rol->puede($modulo);
    }

    /** Quien ve historiales clínicos (módulo "pacientes") debe usar dos pasos. */
    public function debeUsarDosPasos(): bool
    {
        // 25/11/2026: con el módulo dos_pasos apagado no se exige (no habría
        // dónde activarla y el panel quedaría en un ciclo de redirecciones).
        return $this->puede('pacientes')
            && $this->puede('dos_pasos')
            && (bool) config('dentissa.seguridad.dos_pasos_admin_obligatorio');
    }

    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'id_usuario');
    }

    /** Consultas clínicas que registró (historial_clinico.id_doctor). Con alguna, no se elimina. */
    public function consultasRegistradas(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_doctor');
    }

    public function notificacionesSistema(): HasMany
    {
        return $this->hasMany(NotificacionSistema::class);
    }
}
