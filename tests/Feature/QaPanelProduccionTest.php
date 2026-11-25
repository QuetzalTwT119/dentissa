<?php

namespace Tests\Feature;

use App\Enums\EstadoTestimonio;
use App\Enums\RolUsuario;
use App\Models\NotificacionSistema;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Models\User;
use App\Rules\TelefonoMexico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * QA del panel en producción (25/11/2026): cada prueba cubre un hallazgo.
 */
class QaPanelProduccionTest extends TestCase
{
    use RefreshDatabase;

    private User $doctora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctora = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    /** Hallazgo A: aprobar un testimonio no cerraba su aviso en la campana. */
    public function test_aprobar_el_ultimo_testimonio_pendiente_cierra_su_aviso(): void
    {
        [$primero, $segundo] = [$this->testimonio(), $this->testimonio()];
        $aviso = $this->avisoTestimonio();

        // Aún queda otro pendiente: el aviso sigue.
        $this->actingAs($this->doctora)->patch(route('admin.testimonios.aprobar', $primero))->assertRedirect();
        $this->assertNull($aviso->fresh()->atendida_en);

        // Ya no queda ninguno: el aviso sale de «Por atender» y ya no cuenta en la campana.
        $this->actingAs($this->doctora)->patch(route('admin.testimonios.rechazar', $segundo))->assertRedirect();
        $aviso->refresh();
        $this->assertNotNull($aviso->atendida_en);
        $this->assertNotNull($aviso->leida_en);
        $this->assertNotNull($aviso->vista_en);
        $this->assertSame(0, $this->doctora->notificacionesSistema()->porAtender()->count());
    }

    public function test_eliminar_el_ultimo_testimonio_pendiente_tambien_cierra_su_aviso(): void
    {
        $testimonio = $this->testimonio();
        $aviso = $this->avisoTestimonio();

        $this->actingAs($this->doctora)->delete(route('admin.testimonios.destroy', $testimonio))->assertRedirect();

        $this->assertNotNull($aviso->fresh()->atendida_en);
    }

    /** Hallazgo B: se guardó un paciente con «0000000000». */
    public function test_la_regla_rechaza_celulares_que_no_pueden_existir(): void
    {
        $valida = fn (string $telefono) => Validator::make(['t' => $telefono], ['t' => [new TelefonoMexico]])->passes();

        foreach (['7551131833', '755 113 1833', '+52 755 113 1833', '5512345678', '4441234567'] as $bueno) {
            $this->assertTrue($valida($bueno), "{$bueno} debería aceptarse");
        }
        foreach (['0000000000', '0123456789', '1234567890', '5555555555', '+52 000 000 0000'] as $malo) {
            $this->assertFalse($valida($malo), "{$malo} debería rechazarse");
        }
    }

    public function test_nuevo_paciente_y_agenda_en_linea_rechazan_0000000000(): void
    {
        $this->actingAs($this->doctora)->from(route('admin.pacientes.create'))
            ->post(route('admin.pacientes.store'), ['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '0000000000', 'acepta_aviso' => '1'])
            ->assertSessionHasErrors(['telefono' => 'Ese número no parece un celular real. Revisa los 10 dígitos, por ejemplo 755 113 1833.']);
        $this->assertSame(0, Paciente::count());

        auth()->logout();
        $this->post(route('citas.publica.store'), ['telefono' => '0000000000'])
            ->assertSessionHasErrors('telefono');
    }

    /** Hallazgo C: «Tasa de ocupación 0.6 %» mezclaba citas completadas con espacios de 30 min. */
    public function test_la_ocupacion_compara_minutos_con_citas_contra_minutos_de_atencion(): void
    {
        \App\Models\HorarioAtencion::query()->delete();
        foreach (range(0, 6) as $dia) {
            \App\Models\HorarioAtencion::create(['dia_semana' => $dia, 'abierto' => $dia >= 1 && $dia <= 5, 'hora_apertura' => 9, 'hora_cierre' => 18, 'capacidad_simultanea' => 1]);
        }
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5512345678']);
        $cita = fn (string $fecha, int $minutos, string $estado) => \App\Models\Cita::create(['id_paciente' => $paciente->id, 'fecha_hora' => $fecha, 'duracion_estimada' => $minutos, 'estado' => $estado]);
        $cita('2026-12-07 09:00', 60, 'confirmada');
        $cita('2026-12-08 10:00', 60, 'agendada');
        $cita('2026-12-09 11:00', 120, 'completada');
        $cita('2026-12-10 12:00', 60, 'cancelada'); // no ocupa el horario

        // Lunes 7 a viernes 11: 5 días × 9 h = 2700 min; con citas 240 min → 8.9 %.
        $ocupacion = app(\App\Services\ReporteService::class)->generar('2026-12-07', '2026-12-11')['ocupacion'];
        $this->assertSame(2700, $ocupacion['capacidad_total']);
        $this->assertSame(240, $ocupacion['minutos_ocupados']);
        $this->assertSame(1, $ocupacion['citas_atendidas']);
        $this->assertEquals(8.9, $ocupacion['tasa_ocupacion']);

        // Un día bloqueado completo (festivo) no cuenta como tiempo disponible: 2160 min → 11.1 %.
        \App\Models\BloqueoAgenda::create(['fecha_inicio' => '2026-12-11', 'fecha_fin' => '2026-12-11', 'motivo' => 'Festivo']);
        $this->assertEquals(11.1, app(\App\Services\ReporteService::class)->generar('2026-12-07', '2026-12-11')['ocupacion']['tasa_ocupacion']);
    }

    /** Hallazgo D: un tratamiento sin duración no ocupaba tiempo en la agenda. */
    public function test_un_tratamiento_necesita_duracion_de_15_a_480_minutos(): void
    {
        $guardar = fn (array $datos) => $this->actingAs($this->doctora)->from(route('admin.tratamientos.create'))
            ->post(route('admin.tratamientos.store'), $datos + ['nombre' => 'Guarda Oclusal', 'precio_referencia' => 500]);

        $guardar([])->assertSessionHasErrors(['duracion_estimada' => 'Escribe cuántos minutos dura: es lo que ocupa en la agenda.']);
        $guardar(['duracion_estimada' => 0])->assertSessionHasErrors(['duracion_estimada' => 'La duración mínima es de 15 minutos.']);
        $guardar(['duracion_estimada' => 600])->assertSessionHasErrors('duracion_estimada');
        $this->assertFalse(\App\Models\Tratamiento::where('nombre', 'Guarda Oclusal')->exists());

        $guardar(['duracion_estimada' => 45])->assertSessionHasNoErrors();
        $this->assertSame(45, (int) \App\Models\Tratamiento::where('nombre', 'Guarda Oclusal')->value('duracion_estimada'));
    }

    /** Hallazgos E y F: Nueva cita sin orden alfabético, sin acceso a registrar paciente ni aviso sin tratamientos. */
    public function test_nueva_cita_ordena_pacientes_y_explica_especialidades_sin_tratamientos(): void
    {
        Paciente::create(['nombre' => 'Zoe', 'apellido' => 'Luna', 'telefono' => '5512340001']);
        Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '5512340002']);
        Paciente::create(['nombre' => 'Mario', 'apellido' => 'Paz', 'telefono' => '5512340003']);

        $this->actingAs($this->doctora)->get(route('citas.create'))->assertOk()
            ->assertSeeInOrder(['Ana Ruiz', 'Mario Paz', 'Zoe Luna'])
            ->assertSee('Registrar paciente nuevo')
            ->assertSee('href="'.route('admin.pacientes.create').'"', false)
            ->assertSee('data-sin-tratamientos', false)
            ->assertSee('todavía no tiene tratamientos publicados');
    }

    /** Hallazgo G: desde la Agenda no se podía abrir el expediente ni llamar al paciente. */
    public function test_la_agenda_enlaza_al_expediente_y_permite_llamar(): void
    {
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '755 113 1833']);
        $cita = \App\Models\Cita::create(['id_paciente' => $paciente->id, 'fecha_hora' => now()->setTime(12, 0), 'duracion_estimada' => 60, 'estado' => 'confirmada']);
        $url = route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]);

        $this->actingAs($this->doctora)->get($url)->assertOk()
            ->assertSee('href="'.route('admin.pacientes.historial.index', $paciente).'"', false)
            ->assertSee('href="tel:7551131833"', false);

        // La asistente no tiene expedientes: ve el nombre sin enlace, pero sí puede llamar.
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $this->actingAs($asistente)->get($url)->assertOk()
            ->assertDontSee('data-cita-expediente', false)
            ->assertSee('href="tel:7551131833"', false);
    }

    /** Hallazgo H: «Cancelar» era un botón dentro de un enlace (HTML inválido, se leía dos veces). */
    public function test_ningun_boton_va_dentro_de_un_enlace(): void
    {
        $malos = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views'))) as $archivo) {
            if (str_ends_with($archivo->getFilename(), '.blade.php')
                && preg_match('~<a\b[^>]*>\s*<(x-secondary-button|x-primary-button|button)\b~u', file_get_contents($archivo->getPathname()))) {
                $malos[] = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $archivo->getPathname());
            }
        }

        $this->assertSame([], $malos, "Usa <x-boton-cancelar :href=\"...\" /> en lugar de un botón dentro de un enlace:\n".implode("\n", $malos));

        $html = $this->actingAs($this->doctora)->get(route('admin.pacientes.create'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('~<a href="[^"]*/admin/pacientes"[^>]*>\s*Cancelar\s*</a>~', $html);
    }

    private function testimonio(): Testimonio
    {
        $paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Ruiz', 'telefono' => '55'.random_int(10000000, 99999999)]);

        return Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => 5, 'comentario' => 'Muy buena atención.', 'estado' => EstadoTestimonio::Pendiente]);
    }

    private function avisoTestimonio(): NotificacionSistema
    {
        return NotificacionSistema::create([
            'user_id' => $this->doctora->id,
            'evento' => 'testimonio_nuevo',
            'titulo' => 'Nuevo testimonio por revisar',
            'mensaje' => 'Ana dejó un testimonio de 5 estrellas.',
            'url' => route('notificaciones.index', [], false),
        ]);
    }
}
