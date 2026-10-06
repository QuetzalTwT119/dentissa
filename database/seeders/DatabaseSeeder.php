<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'nombre' => 'Melissa',
            'apellido' => 'López N.',
            'email' => 'admin@dentissa.com',
            'telefono' => '0000000000',
            'rol' => RolUsuario::Admin,
        ]);

        User::factory()->create([
            'nombre' => 'Asistente',
            'apellido' => 'Dentissa',
            'email' => 'asistente@dentissa.com',
            'telefono' => '0000000001',
            'rol' => RolUsuario::Asistente,
        ]);

        Paciente::create([
            'nombre' => 'Paciente',
            'apellido' => 'Demo',
            'email' => 'paciente@dentissa.com',
            'telefono' => '0000000002',
        ]);

        $this->call(ServiciosTratamientosSeeder::class);
    }
}
