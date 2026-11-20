<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Events\NotificacionSistemaCreada;
use App\Models\NotificacionSistema;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\Testimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Revisión del 20/11/2026: la portada no mostraba ofertas ni los testimonios
 * aprobados (tenía tarjetas fijas) y el paciente no podía escribir el suyo.
 */
class TestimoniosYPromocionesPublicasTest extends TestCase
{
    use RefreshDatabase;

    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([NotificacionSistemaCreada::class]);
        Mail::fake();
        $this->paciente = Paciente::create(['nombre' => 'Ana', 'apellido' => 'Martínez', 'telefono' => '55 1234 5678', 'email' => 'ana@example.test']);
    }

    private function enviar(array $datos = [])
    {
        return $this->post(route('testimonios.publico.store'), $datos + [
            'contacto' => '+52 (55) 1234-5678',
            'calificacion' => 5,
            'comentario' => 'Me atendieron con mucha paciencia y sin dolor.',
            'autorizacion' => '1',
        ]);
    }

    public function test_el_paciente_deja_su_testimonio_y_queda_pendiente_con_aviso_a_la_clinica(): void
    {
        User::factory()->create(['rol' => RolUsuario::Admin, 'activo' => true]);

        $this->enviar()
            ->assertRedirect(url('/').'#testimonios')
            ->assertSessionHas('testimonio_enviado');

        $testimonio = Testimonio::firstOrFail();
        $this->assertSame($this->paciente->id, $testimonio->id_paciente);
        $this->assertSame('pendiente', $testimonio->estado->value);
        $this->assertSame(1, NotificacionSistema::where('evento', 'testimonio_nuevo')->count());

        // Pendiente: todavía no sale en la portada.
        $this->get('/')->assertOk()->assertDontSee('Me atendieron con mucha paciencia');
    }

    public function test_aprobado_se_publica_con_nombre_e_inicial_y_sin_tarjetas_de_ejemplo(): void
    {
        Testimonio::create(['id_paciente' => $this->paciente->id, 'calificacion' => 4, 'comentario' => 'Excelente trato y muy puntuales.', 'estado' => 'aprobado', 'fecha_publicacion' => now()]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Excelente trato y muy puntuales.')
            ->assertSee('Ana M.')
            ->assertSee('4 de 5 estrellas')
            ->assertSee('data-carrusel-testimonios', false)
            ->assertDontSee('Testimonio pendiente de autorización')
            ->assertDontSee('Martínez');
    }

    public function test_un_contacto_desconocido_recibe_la_misma_respuesta_y_no_se_guarda(): void
    {
        $this->enviar(['contacto' => '55 9999 0000'])->assertSessionHas('testimonio_enviado');
        $this->enviar(['contacto' => 'nadie@example.test'])->assertSessionHas('testimonio_enviado');
        $this->enviar(['sitio_web' => 'http://spam.example'])->assertSessionHas('testimonio_enviado'); // trampa para robots

        $this->assertSame(0, Testimonio::count());
    }

    public function test_con_errores_regresa_a_la_seccion_con_lo_escrito(): void
    {
        $this->from('/')->post(route('testimonios.publico.store'), ['contacto' => 'ana@example.test', 'comentario' => 'corto'])
            ->assertRedirect(url('/').'#testimonios')
            ->assertSessionHasErrors(['calificacion', 'comentario', 'autorizacion'], null, 'testimonio');

        $this->assertSame(0, Testimonio::count());
    }

    public function test_la_portada_muestra_promociones_vigentes(): void
    {
        $this->get('/')->assertOk()->assertDontSee('id="promociones"', false);

        Oferta::create(['titulo' => 'Limpieza de temporada', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15,
            'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addWeek()->toDateString(), 'cupo_total' => 10, 'cupos_utilizados' => 3, 'activa' => true]);
        Oferta::create(['titulo' => 'Oferta vencida', 'tipo' => 'descuento', 'tipo_valor' => 'monto_fijo', 'valor_descuento' => 100,
            'fecha_inicio' => now()->subMonth()->toDateString(), 'fecha_fin' => now()->subDay()->toDateString(), 'activa' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="promociones"', false)
            ->assertSee('Limpieza de temporada')
            ->assertSee('−15%')
            ->assertSee('Quedan 7 lugares')
            ->assertDontSee('Oferta vencida');
    }
}
