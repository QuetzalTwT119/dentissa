<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@dentissa.com'], [
            'nombre' => 'Melissa',
            'apellido' => 'López N.',
            'email' => 'admin@dentissa.com',
            'telefono' => '0000000000',
            'password' => Hash::make('password'),
            'rol' => RolUsuario::SuperAdmin,
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'asistente@dentissa.com'], [
            'nombre' => 'Asistente',
            'apellido' => 'Dentissa',
            'email' => 'asistente@dentissa.com',
            'telefono' => '0000000001',
            'password' => Hash::make('password'),
            'rol' => RolUsuario::Asistente,
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        Paciente::updateOrCreate(['email' => 'paciente@dentissa.com'], [
            'nombre' => 'Paciente',
            'apellido' => 'Demo',
            'email' => 'paciente@dentissa.com',
            'telefono' => '0000000002',
        ]);

        $this->call(ServiciosTratamientosSeeder::class);
    }
}
