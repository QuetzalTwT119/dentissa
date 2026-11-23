<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vista semanal "Bloques": cada día en bloques de 2 horas con su nivel de
 * ocupación (libre, con citas, lleno) y los horarios donde todavía cabe una
 * cita. Horario por defecto: lunes a viernes 8–18, sábado 9–14, capacidad 2.
 */
class OcupacionSemanalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2030-01-09 10:00:00'); // miércoles

        $this->admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $this->servicio = Servicio::create(['nombre' => 'Odontología General', 'orden' => 1, 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza Dental', 'duracion_estimada' => 60, 'activo' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function semana(): string
    {
        return $this->actingAs($this->admin)
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2030-01-07']))
            ->assertOk()
            ->getContent();
    }

    public function test_cada_bloque_dice_si_esta_libre_con_citas_o_lleno(): void
    {
        // Jueves 8–10: dos citas de 2 h ocupan la capacidad completa.
        $this->cita('2030-01-10 08:00', 120);
        $this->cita('2030-01-10 08:00', 120);
        // Jueves 10–12: una sola cita de 1 h; aún hay lugar.
        $this->cita('2030-01-10 10:00', 60);

        $html = $this->semana();

        $this->assertStringContainsString('data-bloque="2030-01-10|8" data-nivel="lleno"', $html);
        $this->assertStringContainsString('data-bloque="2030-01-10|10" data-nivel="con_citas"', $html);
        $this->assertStringContainsString('data-bloque="2030-01-10|12" data-nivel="libre"', $html);
    }

    public function test_una_cita_cancelada_no_ocupa_lugar(): void
    {
        $this->cita('2030-01-10 08:00', 120, EstadoCita::Cancelada);
        $this->cita('2030-01-10 08:00', 120, EstadoCita::Cancelada);

        $this->assertStringContainsString('data-bloque="2030-01-10|8" data-nivel="libre"', $this->semana());
    }

    public function test_los_horarios_con_lugar_omiten_lo_que_ya_paso_y_lo_lleno(): void
    {
        $this->cita('2030-01-10 10:00', 60);
        $this->cita('2030-01-10 10:00', 60); // 10:00–11:00 lleno

        $html = $this->semana();

        $detalle = $this->detalle($html, '2030-01-10|10');
        // Los botones de horario libre se anuncian como "… a las HH:MM".
        $this->assertStringContainsString('a las 11:00"', $detalle);
        $this->assertStringContainsString('a las 11:30"', $detalle);
        $this->assertStringNotContainsString('a las 10:00"', $detalle);
        $this->assertStringNotContainsString('a las 10:30"', $detalle);
        $this->assertStringContainsString(route('citas.create', ['fecha' => '2030-01-10']), $detalle);

        // Miércoles 8–10 ya pasó (son las 10:00): no ofrece horarios.
        $this->assertStringNotContainsString('Dónde cabe una cita', $this->detalle($html, '2030-01-09|8'));
    }

    public function test_el_sabado_respeta_su_horario_corto_y_el_bloque_elegido_es_el_actual(): void
    {
        $html = $this->semana();

        // Sábado 9–14: el bloque de 8 empieza a las 9 y no hay bloque de 14.
        $this->assertStringContainsString('data-bloque="2030-01-12|8"', $html);
        $this->assertStringContainsString('9:00 a 10:00', $this->detalle($html, '2030-01-12|8'));
        $this->assertStringNotContainsString('data-bloque="2030-01-12|14"', $html);

        // Hoy (miércoles, 10:00) se abre el primer bloque que no ha terminado.
        $this->assertStringContainsString("x-data=\"{ bloque: '2030-01-09|10' }\"", $html);
        $this->assertStringContainsString('data-detalle="2030-01-09|10"', $html);
    }

    public function test_si_hoy_ya_cerro_se_abre_el_siguiente_bloque_disponible(): void
    {
        Carbon::setTestNow('2030-01-09 19:00:00'); // miércoles, ya cerró

        $this->assertStringContainsString("x-data=\"{ bloque: '2030-01-10|8' }\"", $this->semana());

        // Una semana que ya pasó completa abre su primer bloque.
        $html = $this->actingAs($this->admin)
            ->get(route('citas.index', ['vista' => 'semana', 'semana' => '2029-12-31']))
            ->getContent();
        $this->assertStringContainsString("x-data=\"{ bloque: '2029-12-31|8' }\"", $html);
    }

    public function test_un_dia_cerrado_con_citas_las_sigue_mostrando(): void
    {
        // Domingo cerrado (horario por defecto) con una cita registrada.
        $this->cita('2030-01-13 10:00', 60);

        $html = $this->semana();

        $this->assertStringContainsString('Cerrado · 1 cita', $html);
        $this->assertStringContainsString('Ana Prueba', $this->detalle($html, '2030-01-13|cerrado'));
        $this->assertStringNotContainsString('data-bloque="2030-01-13|', $html);
    }

    public function test_agendar_desde_un_horario_libre_trae_la_fecha(): void
    {
        $this->actingAs($this->admin)->get(route('citas.create', ['fecha' => '2030-01-10']))
            ->assertOk()
            ->assertSee("fecha: '2030-01-10'", false);

        $this->actingAs($this->admin)->get(route('citas.create', ['fecha' => '<script>']))
            ->assertOk()
            ->assertSee("fecha: ''", false);
    }

    /** HTML del panel de detalle de un bloque. */
    private function detalle(string $html, string $clave): string
    {
        $inicio = strpos($html, 'data-detalle="'.$clave.'"');
        $this->assertNotFalse($inicio, "No hay detalle para {$clave}");
        $fin = strpos($html, 'data-detalle="', $inicio + 10);

        return substr($html, $inicio, $fin === false ? null : $fin - $inicio);
    }

    private function cita(string $fechaHora, int $duracion, EstadoCita $estado = EstadoCita::Agendada): Cita
    {
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '55'.random_int(10000000, 99999999),
            'email' => fake()->unique()->safeEmail(),
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => $duracion,
            'estado' => $estado,
        ]);
    }
}
