<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoPresentacionSeeder extends Seeder
{
    /**
     * Crea solo las cuentas y registros ficticios necesarios para la demo.
     * Requiere habilitación y contraseñas privadas explícitas; nunca usa
     * credenciales predeterminadas.
     */
    public function run(): void
    {
        if (config('services.demo_presentacion.habilitada') !== true) {
            throw new RuntimeException(
                'El seeder de demostración está deshabilitado. Activa DENTISSA_DEMO_SEED_ENABLED solo para ejecutarlo manualmente.'
            );
        }

        $admin = $this->validarCuenta('admin');
        $asistente = $this->validarCuenta('asistente');

        if (mb_strtolower($admin['email']) === mb_strtolower($asistente['email'])) {
            throw new RuntimeException('Las cuentas demo de administrador y asistente deben tener correos distintos.');
        }

        if (hash_equals($admin['password'], $asistente['password'])) {
            throw new RuntimeException('Las cuentas demo de administrador y asistente deben tener contraseñas distintas.');
        }

        DB::transaction(function () use ($admin, $asistente): void {
            $this->guardarCuenta($admin, RolUsuario::SuperAdmin, 'Admin', 'Demostración');
            $this->guardarCuenta($asistente, RolUsuario::Asistente, 'Asistente', 'Demostración');

            $this->call(ServiciosTratamientosSeeder::class);

            Paciente::updateOrCreate(
                ['email' => 'paciente.demo@example.invalid'],
                [
                    'nombre' => 'Paciente',
                    'apellido' => 'Demostración',
                    'telefono' => '5550000000',
                ],
            );
        });
    }

    /** @return array{email: string, password: string} */
    private function validarCuenta(string $rol): array
    {
        $email = trim((string) config("services.demo_presentacion.{$rol}.email", ''));
        $password = (string) config("services.demo_presentacion.{$rol}.password", '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException("Configura un correo válido para la cuenta demo de {$rol}.");
        }

        // Bcrypt procesa hasta 72 bytes; exigimos además una contraseña larga,
        // aleatoria y distinta para cada cuenta, sin un valor de reserva.
        $longitud = strlen($password);

        if ($longitud < 20 || $longitud > 72 || $password === 'password') {
            throw new RuntimeException("La contraseña demo de {$rol} debe tener entre 20 y 72 bytes y no usar valores predeterminados.");
        }

        return compact('email', 'password');
    }

    /** @param array{email: string, password: string} $cuenta */
    private function guardarCuenta(array $cuenta, RolUsuario $rol, string $nombre, string $apellido): void
    {
        User::updateOrCreate(
            ['email' => $cuenta['email']],
            [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'telefono' => '5550000000',
                'password' => Hash::make($cuenta['password']),
                'rol' => $rol,
                'activo' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
