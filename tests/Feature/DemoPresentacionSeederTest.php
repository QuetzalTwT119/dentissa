<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\DemoPresentacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoPresentacionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_de_demo_se_rechaza_si_no_se_habilita_expresamente(): void
    {
        config()->set('services.demo_presentacion.habilitada', false);

        try {
            app(DemoPresentacionSeeder::class)->run();
            $this->fail('Se esperaba que el seeder rechazara una ejecución deshabilitada.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('deshabilitado', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_seeder_crea_cuentas_y_catalogo_ficticios_de_forma_repetible(): void
    {
        config()->set('services.demo_presentacion', [
            'habilitada' => true,
            'admin' => [
                'email' => 'admin.demo@example.invalid',
                'password' => 'ClaveAdminDemo-98x!random',
            ],
            'asistente' => [
                'email' => 'asistente.demo@example.invalid',
                'password' => 'ClaveAsistenteDemo-74x!random',
            ],
        ]);

        app(DemoPresentacionSeeder::class)->run();
        app(DemoPresentacionSeeder::class)->run();

        $admin = User::where('email', 'admin.demo@example.invalid')->firstOrFail();
        $asistente = User::where('email', 'asistente.demo@example.invalid')->firstOrFail();

        $this->assertSame(RolUsuario::Admin, $admin->rol);
        $this->assertSame(RolUsuario::Asistente, $asistente->rol);
        $this->assertTrue(password_verify('ClaveAdminDemo-98x!random', $admin->password));
        $this->assertTrue(password_verify('ClaveAsistenteDemo-74x!random', $asistente->password));
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('pacientes', 1);
        $this->assertDatabaseHas('pacientes', ['email' => 'paciente.demo@example.invalid']);
        $this->assertGreaterThan(0, Servicio::count());
        $this->assertDatabaseCount('tratamientos', 8);
    }

    public function test_seeder_rechaza_credenciales_de_reserva_o_reutilizadas(): void
    {
        config()->set('services.demo_presentacion', [
            'habilitada' => true,
            'admin' => ['email' => 'admin.demo@example.invalid', 'password' => 'password'],
            'asistente' => ['email' => 'asistente.demo@example.invalid', 'password' => 'password'],
        ]);

        try {
            app(DemoPresentacionSeeder::class)->run();
            $this->fail('Se esperaba que el seeder rechazara contraseñas débiles.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('20 y 72 bytes', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }
}
