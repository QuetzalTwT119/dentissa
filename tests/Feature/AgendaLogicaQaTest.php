<?php

namespace Tests\Feature;

use App\Enums\EstadoCita;
use App\Enums\RolUsuario;
use App\Models\Cita;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * QA de la agenda en producción (24/11/2026). Cada prueba es un hallazgo:
 * 1) "citas activas" contaba las completadas;
 * 2) se podían tener dos citas «En curso» a la vez y quedaban así días después;
 * 3) una cita ya terminada seguía ofreciendo «Iniciar»;
 * 4) se podían iniciar citas de días futuros.
 */
class AgendaLogicaQaTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();
        // Martes 24 de noviembre de 2026, 12:56 (como en el QA).
        Carbon::setTestNow(Carbon::parse('2026-11-24 12:56:00', 'America/Mexico_City'));
        HorarioAtencion::query()->delete();
        foreach (range(0, 6) as $dia) {
            HorarioAtencion::create(['dia_semana' => $dia, 'abierto' => $dia >= 1 && $dia <= 5, 'hora_apertura' => 8, 'hora_cierre' => 18, 'capacidad_simultanea' => 1]);
        }
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function cita(string $nombre, string $fechaHora, EstadoCita $estado, int $minutos = 60): Cita
    {
        $paciente = Paciente::create(['nombre' => $nombre, 'apellido' => 'QA', 'telefono' => '55'.random_int(10000000, 99999999)]);

        return Cita::create(['id_paciente' => $paciente->id, 'fecha_hora' => $fechaHora, 'duracion_estimada' => $minutos, 'estado' => $estado]);
    }

    public function test_las_completadas_no_cuentan_como_por_atender(): void
    {
        $this->cita('Ana', '2026-11-23 09:00', EstadoCita::Completada);
        $this->cita('Beto', '2026-11-23 10:00', EstadoCita::Completada);
        $this->cita('Caro', '2026-11-23 11:00', EstadoCita::Confirmada);
        $this->cita('Dani', '2026-11-23 12:00', EstadoCita::Cancelada);

        $html = $this->actingAs($this->doctora)->get(route('citas.index', ['fecha' => '2026-11-23']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#1 por atender\s*·\s*2 atendidas\s*·\s*1 cancelada#u', $html);
        $this->assertStringNotContainsString('citas activas', $html);
        // El renglón plegado ya no repite "ya atendidas o canceladas (…)".
        $this->assertStringContainsString('2 atendidas · 1 cancelada', $html);
        $this->assertStringNotContainsString('ya atendidas o canceladas', $html);
    }

    public function test_no_deja_iniciar_otra_cita_mientras_hay_una_en_curso(): void
    {
        $this->cita('Thayna', '2026-11-24 12:00', EstadoCita::EnCurso, 90);
        $siguiente = $this->cita('Juan', '2026-11-24 12:50', EstadoCita::Confirmada, 30);

        $this->actingAs($this->doctora)->patch(route('citas.start', $siguiente))
            ->assertSessionHasErrors(['estado' => 'Primero completa la cita de Thayna QA (12:00), que sigue en curso.']);
        $this->assertSame(EstadoCita::Confirmada, $siguiente->fresh()->estado);
    }

    public function test_con_capacidad_de_dos_si_deja_dos_en_curso(): void
    {
        HorarioAtencion::query()->update(['capacidad_simultanea' => 2]);
        $this->cita('Thayna', '2026-11-24 12:00', EstadoCita::EnCurso, 90);
        $siguiente = $this->cita('Juan', '2026-11-24 12:50', EstadoCita::Confirmada, 30);

        $this->actingAs($this->doctora)->patch(route('citas.start', $siguiente))->assertSessionHasNoErrors();
        $this->assertSame(EstadoCita::EnCurso, $siguiente->fresh()->estado);
    }

    public function test_avisa_las_citas_que_quedaron_en_curso_de_dias_anteriores(): void
    {
        $this->cita('Osiris', '2026-11-23 12:30', EstadoCita::EnCurso);
        $this->cita('Osiris Dos', '2026-11-23 13:30', EstadoCita::EnCurso);

        $this->actingAs($this->doctora)->get(route('citas.index'))
            ->assertOk()
            ->assertSee('data-en-curso-olvidadas', false)
            ->assertSee('2 citas quedaron «En curso» de días anteriores.')
            ->assertSee('fecha=2026-11-23', false);

        // Una vez completadas, el aviso desaparece.
        Cita::query()->update(['estado' => EstadoCita::Completada]);
        $this->actingAs($this->doctora)->get(route('citas.index'))->assertDontSee('data-en-curso-olvidadas', false);
    }

    public function test_una_cita_que_ya_termino_ofrece_atendida_y_no_asistio_en_vez_de_iniciar(): void
    {
        $pasada = $this->cita('Thayna', '2026-11-24 10:00', EstadoCita::Confirmada, 90); // terminó 11:30
        $ahora = $this->cita('Juan', '2026-11-24 12:30', EstadoCita::Confirmada, 60);    // termina 13:30

        $html = $this->actingAs($this->doctora)->get(route('citas.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('citas.start', $pasada), $html);
        $this->assertStringContainsString(route('citas.complete', $pasada), $html);
        $this->assertStringContainsString(route('citas.no-show', $pasada), $html);
        // La que está en su horario sí se inicia.
        $this->assertStringContainsString(route('citas.start', $ahora), $html);

        // Iniciarla ya no se permite; marcarla «Atendida» es un solo paso.
        $this->actingAs($this->doctora)->patch(route('citas.start', $pasada))->assertSessionHasErrors('estado');
        $this->actingAs($this->doctora)->patch(route('citas.complete', $pasada))
            ->assertSessionHas('success', 'Cita marcada como atendida.');
        $this->assertSame(EstadoCita::Completada, $pasada->fresh()->estado);
        $this->assertDatabaseHas('logs_auditoria', ['accion' => 'cita.completada', 'registro_id' => $pasada->id]);
    }

    public function test_una_agendada_que_nunca_se_confirmo_y_ya_paso_se_puede_marcar_no_asistio(): void
    {
        $pasada = $this->cita('Karina', '2026-11-24 09:00', EstadoCita::Agendada, 60);

        $this->actingAs($this->doctora)->patch(route('citas.no-show', $pasada))->assertSessionHasNoErrors();
        $this->assertSame(EstadoCita::NoAsistio, $pasada->fresh()->estado);
    }

    public function test_no_se_inician_ni_se_marcan_atendidas_las_citas_de_dias_futuros(): void
    {
        $manana = $this->cita('Mery', '2026-11-25 10:00', EstadoCita::Confirmada);

        $html = $this->actingAs($this->doctora)->get(route('citas.index', ['fecha' => '2026-11-25']))->assertOk()->getContent();
        $this->assertStringNotContainsString(route('citas.start', $manana), $html);
        $this->assertStringNotContainsString(route('citas.complete', $manana), $html);

        $this->actingAs($this->doctora)->patch(route('citas.start', $manana))
            ->assertSessionHasErrors(['estado' => 'Una cita solo se inicia el día que le toca (25 de noviembre).']);
        $this->actingAs($this->doctora)->patch(route('citas.complete', $manana))
            ->assertSessionHasErrors(['estado' => 'Una cita solo se marca como atendida cuando llega su hora.']);
        $this->assertSame(EstadoCita::Confirmada, $manana->fresh()->estado);
    }
}
