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
