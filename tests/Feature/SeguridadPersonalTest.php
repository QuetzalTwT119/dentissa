<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\User;
use App\Services\DosPasosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Fase 3 para pacientes reales: seguridad de las cuentas del personal.
 */
class SeguridadPersonalTest extends TestCase
{
    use RefreshDatabase;

    // ── Cuentas desactivadas ──────────────────────────────────────────────

    public function test_una_cuenta_desactivada_pierde_la_sesion_al_instante(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente, 'activo' => true]);
        // 22/11/2026: la asistente solo tiene el panel de inicio (config/permisos.php).
        $this->actingAs($asistente)->get(route('dashboard'))->assertOk();

        $asistente->update(['activo' => false]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Tu cuenta fue desactivada. Si crees que es un error, habla con el administrador.']);
        $this->assertGuest();
    }

    // ── Cabeceras ─────────────────────────────────────────────────────────

    public function test_todas_las_paginas_envian_cabeceras_de_seguridad(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');

        // HSTS solo tiene sentido cuando la petición ya llegó por HTTPS.
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    // ── Contraseñas y sesiones ────────────────────────────────────────────

    public function test_las_contrasenas_nuevas_deben_ser_fuertes(): void
    {
        $usuario = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($usuario)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($usuario)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('ClaveSegura2026', $usuario->fresh()->password));
    }

    public function test_cambiar_la_contrasena_cierra_las_demas_sesiones(): void
    {
        config(['session.driver' => 'database']);
        $usuario = User::factory()->create(['rol' => RolUsuario::Asistente, 'remember_token' => 'token-anterior']);
        foreach (['sesion-laptop', 'sesion-recepcion'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $usuario->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'prueba', 'payload' => '', 'last_activity' => now()->timestamp]);
        }

        $this->actingAs($usuario)->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'ClaveSegura2026',
            'password_confirmation' => 'ClaveSegura2026',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('sessions')->whereIn('id', ['sesion-laptop', 'sesion-recepcion'])->count());
        $this->assertNotSame('token-anterior', $usuario->fresh()->remember_token);
    }

    public function test_la_sesion_caduca_tras_30_minutos_sin_actividad(): void
    {
        // Se evalúa el valor por defecto del código, sin el .env del equipo.
        $respaldo = [$_ENV['SESSION_LIFETIME'] ?? null, $_SERVER['SESSION_LIFETIME'] ?? null, getenv('SESSION_LIFETIME')];
        unset($_ENV['SESSION_LIFETIME'], $_SERVER['SESSION_LIFETIME']);
        putenv('SESSION_LIFETIME');

        try {
            $this->assertSame(30, (require config_path('session.php'))['lifetime']);
        } finally {
            [$env, $server, $put] = $respaldo;
            $env === null ? null : $_ENV['SESSION_LIFETIME'] = $env;
            $server === null ? null : $_SERVER['SESSION_LIFETIME'] = $server;
            $put === false ? null : putenv("SESSION_LIFETIME={$put}");
        }

        $this->assertStringContainsString("SESSION_LIFETIME=30\n", file_get_contents(base_path('.env.example')));
    }

    // ── Verificación en dos pasos: activar ────────────────────────────────

    public function test_el_personal_activa_la_verificacion_y_recibe_codigos_de_recuperacion(): void
    {
        $usuario = User::factory()->create(['rol' => RolUsuario::Doctor]);

        $this->actingAs($usuario)->get(route('dos-pasos.show'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee('Escanea este código QR');
        $secreto = session('dos_pasos_pendiente');

        $this->post(route('dos-pasos.confirmar'), ['codigo' => '000000'])
            ->assertSessionHasErrors('codigo');
        $this->assertFalse($usuario->fresh()->tieneDosPasos());

        $this->post(route('dos-pasos.confirmar'), ['codigo' => $this->codigo($secreto)])
            ->assertRedirect(route('dos-pasos.show'));

        $usuario->refresh();
        $this->assertTrue($usuario->tieneDosPasos());
        $this->assertCount(DosPasosService::CODIGOS_RECUPERACION, session('dos_pasos_codigos'));
        $this->get(route('dos-pasos.show'))->assertSee(session('dos_pasos_codigos')[0] ?? '');

        // En la base el secreto va cifrado y los códigos solo como hash.
        $crudo = DB::table('users')->where('id', $usuario->id)->first();
        $this->assertNotSame($secreto, $crudo->dos_pasos_secreto);
        $this->assertStringNotContainsString($secreto, (string) $crudo->dos_pasos_secreto);
    }

    // ── Verificación en dos pasos: obligatoria para el administrador ──────

    public function test_el_administrador_sin_verificacion_solo_puede_activarla(): void
    {
        config(['dentissa.seguridad.dos_pasos_admin_obligatorio' => true]);
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);

        $this->actingAs($admin)->get(route('admin.pacientes.index'))->assertRedirect(route('dos-pasos.show'));
        $this->actingAs($admin)->get(route('citas.index'))->assertRedirect(route('dos-pasos.show'));
        $this->actingAs($admin)->get(route('dos-pasos.show'))->assertOk()->assertSee('obligatoria');
        // Las páginas públicas y cerrar sesión siguen disponibles.
        $this->actingAs($admin)->get('/')->assertOk();

        // La asistente no está obligada a usar dos pasos (no ve historiales clínicos).
        $this->actingAs($asistente)->get(route('dashboard'))->assertOk();
    }

    public function test_el_administrador_no_puede_desactivarla_y_la_asistente_no_tiene_acceso(): void
    {
        config(['dentissa.seguridad.dos_pasos_admin_obligatorio' => true]);
        $admin = $this->conDosPasos(User::factory()->create(['rol' => RolUsuario::SuperAdmin]));
        $asistente = $this->conDosPasos(User::factory()->create(['rol' => RolUsuario::Asistente]));

        $this->actingAs($admin)->delete(route('dos-pasos.desactivar'), ['current_password' => 'password'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue($admin->fresh()->tieneDosPasos());

        // 22/11/2026: la asistente solo tiene el panel de inicio; Mi perfil y dos pasos no.
        $this->actingAs($asistente)->delete(route('dos-pasos.desactivar'), ['current_password' => 'password'])
            ->assertForbidden();
        $this->assertTrue($asistente->fresh()->tieneDosPasos());
    }

    // ── Verificación en dos pasos: al iniciar sesión ──────────────────────

    public function test_con_verificacion_la_contrasena_sola_no_abre_sesion(): void
    {
        $admin = $this->conDosPasos(User::factory()->create(['rol' => RolUsuario::SuperAdmin]));

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dos-pasos.reto'));
        $this->assertGuest();

        $this->post(route('dos-pasos.verificar'), ['codigo' => '000000'])->assertSessionHasErrors('codigo');
        $this->assertGuest();

        $codigo = $this->codigo($admin->fresh()->dos_pasos_secreto);
        $this->post(route('dos-pasos.verificar'), ['codigo' => $codigo])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);

        // El mismo código no sirve dos veces (repetición).
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => $codigo])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_un_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        $usuario = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $codigos = $this->activar($usuario);

        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => strtolower($codigos[0])])->assertRedirect(route('dashboard'));
        $this->assertSame(DosPasosService::CODIGOS_RECUPERACION - 1, app(DosPasosService::class)->codigosRestantes($usuario->fresh()));

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password']);
        $this->post(route('dos-pasos.verificar'), ['codigo' => $codigos[0]])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_el_reto_caduca_a_los_cinco_minutos(): void
    {
        $usuario = $this->conDosPasos(User::factory()->create(['rol' => RolUsuario::Asistente]));
        $this->post(route('login'), ['email' => $usuario->email, 'password' => 'password']);

        Carbon::setTestNow(now()->addMinutes(6));
        try {
            $this->post(route('dos-pasos.verificar'), ['codigo' => $this->codigo($usuario->fresh()->dos_pasos_secreto)])
                ->assertRedirect(route('login'));
        } finally {
            Carbon::setTestNow();
        }
        $this->assertGuest();
    }

    public function test_sin_intento_pendiente_el_reto_regresa_al_login(): void
    {
        $this->get(route('dos-pasos.reto'))->assertRedirect(route('login'));
    }

    // ── Ayudantes ─────────────────────────────────────────────────────────

    private function codigo(string $secreto): string
    {
        return app(Google2FA::class)->getCurrentOtp($secreto);
    }

    /** @return list<string> códigos de recuperación en claro */
    private function activar(User $usuario): array
    {
        $servicio = app(DosPasosService::class);
        $secreto = $servicio->nuevoSecreto();

        // Paso anterior al actual: el código vigente sigue sirviendo para entrar.
        return $servicio->activar($usuario, $secreto, 0);
    }

    private function conDosPasos(User $usuario): User
    {
        $this->activar($usuario);

        return $usuario->fresh();
    }
}
