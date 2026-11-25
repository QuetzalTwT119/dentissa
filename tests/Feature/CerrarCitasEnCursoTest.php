<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Criterios de aceptación (25/11/2026):
 * - Una cita «En curso» de un día anterior queda «Completada».
 * - Las de hoy y las de otros estados no se tocan.
 * - La doctora recibe un aviso en la campana; sin pendientes no hay aviso.
 * - --simular no guarda nada.
 * - El programador lo corre cada día y la agenda deja de mostrar el aviso.
 */
class CerrarCitasEnCursoTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Tratamiento $tratamiento;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-25 09:00:00');
        Mail::fake();
        $this->servicio = Servicio::create(['nombre' => 'General', 'activo' => true]);
        $this->tratamiento = Tratamiento::create(['nombre' => 'Limpieza', 'duracion_estimada' => 60, 'activo' => true, 'precio_referencia' => 150]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_completa_las_citas_en_curso_de_dias_anteriores_y_avisa(): void
    {
        $doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $olvidada1 = $this->cita('2026-11-23 12:30', EstadoCita::EnCurso);
        $olvidada2 = $this->cita('2026-11-23 13:30', EstadoCita::EnCurso);
        $deHoy = $this->cita('2026-11-25 08:00', EstadoCita::EnCurso);
        $confirmadaPasada = $this->cita('2026-11-24 10:00', EstadoCita::Confirmada);

        $this->artisan('dentissa:cerrar-citas-en-curso')->assertSuccessful();

        $this->assertSame(EstadoCita::Completada, $olvidada1->fresh()->estado);
        $this->assertSame(EstadoCita::Completada, $olvidada2->fresh()->estado);
        $this->assertSame(EstadoCita::EnCurso, $deHoy->fresh()->estado, 'Las de hoy las cierra la doctora');
        $this->assertSame(EstadoCita::Confirmada, $confirmadaPasada->fresh()->estado, 'Solo toca citas iniciadas');

        $aviso = NotificacionSistema::where('user_id', $doctora->id)->where('evento', 'citas_en_curso_cerradas')->sole();
        $this->assertSame('Se completaron 2 citas olvidadas', $aviso->titulo);
        $this->assertStringContainsString('(2)', $aviso->mensaje);

        // La agenda ya no muestra el aviso amarillo.
        $this->actingAs($doctora)->get(route('citas.index'))->assertOk()->assertDontSee('data-en-curso-olvidadas', false);
    }

    public function test_sin_pendientes_no_avisa_y_se_puede_correr_varias_veces(): void
    {
        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'activo' => true]);
        $this->cita('2026-11-23 12:30', EstadoCita::EnCurso);

        $this->artisan('dentissa:cerrar-citas-en-curso')->assertSuccessful();
        $this->artisan('dentissa:cerrar-citas-en-curso')->assertSuccessful();

        $this->assertSame(1, NotificacionSistema::where('evento', 'citas_en_curso_cerradas')->count());
    }

    public function test_simular_no_guarda_nada(): void
    {
        $cita = $this->cita('2026-11-23 12:30', EstadoCita::EnCurso);

        $this->artisan('dentissa:cerrar-citas-en-curso --simular')->assertSuccessful();

        $this->assertSame(EstadoCita::EnCurso, $cita->fresh()->estado);
        $this->assertSame(0, NotificacionSistema::count());
    }

    public function test_el_programador_lo_corre_cada_dia(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento) => str_contains((string) $evento->command, 'dentissa:cerrar-citas-en-curso'));

        $this->assertCount(1, $eventos);
        $this->assertSame('5 0 * * *', $eventos->first()->expression);
        $this->assertStringContainsString('dentissa:cerrar-citas-en-curso', file_get_contents(base_path('scripts/start-production.sh')));
    }

    private function cita(string $fechaHora, EstadoCita $estado): Cita
    {
        $paciente = Paciente::create([
            'nombre' => 'Ana',
            'apellido' => 'Prueba',
            'telefono' => '55'.random_int(10000000, 99999999),
            'email' => 'ana'.random_int(1, 999999).'@example.test',
        ]);

        return Cita::create([
            'id_paciente' => $paciente->id,
            'id_servicio' => $this->servicio->id,
            'id_tratamiento' => $this->tratamiento->id,
            'fecha_hora' => Carbon::parse($fechaHora),
            'duracion_estimada' => 60,
            'estado' => $estado,
        ]);
    }
}
