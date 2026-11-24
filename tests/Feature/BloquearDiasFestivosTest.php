<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\FestivoOmitido;
use App\Models\HorarioAtencion;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\DisponibilidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BloquearDiasFestivosTest extends TestCase
{
    use RefreshDatabase;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-24 10:00:00');
        Mail::fake();
        HorarioAtencion::query()->delete();
        foreach (range(0, 6) as $dia) {
            HorarioAtencion::create(['dia_semana' => $dia, 'abierto' => $dia !== 0, 'hora_apertura' => 9, 'hora_cierre' => 18, 'capacidad_simultanea' => 1]);
        }
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 30, 'activo' => true, 'precio' => 500]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_bloquea_los_festivos_que_faltan_del_anio_y_del_siguiente_sin_tocar_los_pasados(): void
    {
        $this->artisan('dentissa:bloquear-festivos')->assertSuccessful();

        // 2026: ya pasaron todos salvo 12, 24, 25 y 31 de diciembre. 2027: los 11.
        $this->assertSame(4 + 11, BloqueoAgenda::count());
        $this->assertFalse(BloqueoAgenda::delDia('2026-11-16')->exists(), 'No bloquea fechas pasadas');

        $navidad = BloqueoAgenda::delDia('2026-12-25')->sole();
        $this->assertTrue($navidad->todoElDia());
        $this->assertTrue($navidad->esFestivo());
        $this->assertSame('Festivo: Navidad', $navidad->motivo);

        // La agenda en línea ya no ofrece ese día.
        $this->assertFalse(app(DisponibilidadService::class)->estaDisponible(Carbon::parse('2026-12-25 10:00'), $this->tratamiento));
        $this->assertTrue(app(DisponibilidadService::class)->estaDisponible(Carbon::parse('2026-12-26 10:00'), $this->tratamiento));
    }

    public function test_correrlo_otra_vez_no_duplica_y_respeta_un_bloqueo_que_ya_existia(): void
    {
        BloqueoAgenda::create(['fecha_inicio' => '2026-12-24', 'fecha_fin' => '2026-12-26', 'motivo' => 'Vacaciones']);

        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026]])->assertSuccessful();
        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026]])->assertSuccessful();

        // Solo faltaban el 12 y el 31: el 24 y el 25 ya estaban dentro de las vacaciones.
        $this->assertSame(3, BloqueoAgenda::count());
    }

    public function test_no_bloquea_un_festivo_con_citas_y_avisa_en_la_campana(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
        Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $this->tratamiento->id, 'fecha_hora' => '2026-12-12 10:30', 'duracion_estimada' => 30, 'estado' => 'confirmada']);
        Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $this->tratamiento->id, 'fecha_hora' => '2026-12-24 10:30', 'duracion_estimada' => 30, 'estado' => 'cancelada']);

        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026], '--avisar' => true])
            ->expectsOutputToContain('Sin bloquear')
            ->assertSuccessful();

        $this->assertFalse(BloqueoAgenda::delDia('2026-12-12')->exists(), 'Con una cita activa no se bloquea');
        $this->assertTrue(BloqueoAgenda::delDia('2026-12-24')->exists(), 'Una cita cancelada no lo impide');

        $aviso = NotificacionSistema::where('user_id', $admin->id)->where('evento', 'festivos_con_citas')->sole();
        $this->assertStringContainsString('Virgen de Guadalupe', $aviso->mensaje);
        $this->assertStringContainsString('1 cita activa', $aviso->mensaje);
    }

    public function test_sin_avisar_no_manda_nada_a_la_campana(): void
    {
        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5511112222']);
        Cita::create(['id_paciente' => $paciente->id, 'id_tratamiento' => $this->tratamiento->id, 'fecha_hora' => '2026-12-12 10:30', 'duracion_estimada' => 30, 'estado' => 'agendada']);

        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026]])->assertSuccessful();

        $this->assertSame(0, NotificacionSistema::where('evento', 'festivos_con_citas')->count());
    }

    public function test_simular_no_guarda_nada(): void
    {
        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2027], '--simular' => true])
            ->expectsOutputToContain('Se bloquearía')
            ->assertSuccessful();

        $this->assertSame(0, BloqueoAgenda::count());
    }

    public function test_si_la_doctora_quita_un_festivo_no_se_vuelve_a_poner(): void
    {
        $doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026]])->assertSuccessful();
        $nochebuena = BloqueoAgenda::delDia('2026-12-24')->sole();

        $this->actingAs($doctora)->delete(route('admin.bloqueos.destroy', $nochebuena))
            ->assertRedirect(route('admin.configuracion').'#bloqueos');

        $this->assertTrue(FestivoOmitido::whereDate('fecha', '2026-12-24')->exists());

        $this->artisan('dentissa:bloquear-festivos', ['anios' => [2026]])
            ->expectsOutputToContain('Quitados por la doctora: 1')
            ->assertSuccessful();
        $this->assertFalse(BloqueoAgenda::delDia('2026-12-24')->exists());
    }

    public function test_quitar_un_bloqueo_del_personal_no_lo_marca_como_festivo_omitido(): void
    {
        $doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $bloqueo = BloqueoAgenda::create(['fecha_inicio' => '2026-12-12', 'fecha_fin' => '2026-12-12', 'motivo' => 'Festivo: escrito a mano', 'creado_por' => $doctora->id]);

        $this->actingAs($doctora)->delete(route('admin.bloqueos.destroy', $bloqueo));

        $this->assertSame(0, FestivoOmitido::count());
    }

    public function test_rechaza_un_anio_invalido(): void
    {
        $this->artisan('dentissa:bloquear-festivos', ['anios' => ['27']])
            ->expectsOutputToContain('no es un año válido')
            ->assertFailed();
    }

    public function test_el_programador_lo_corre_cada_mes_con_aviso(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('dentissa:bloquear-festivos --avisar')
            ->assertSuccessful();
    }
}
