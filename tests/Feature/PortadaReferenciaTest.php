<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\DatosConsultorio;
use App\Models\Oferta;
use App\Models\Paciente;
use App\Models\PreguntaFrecuente;
use App\Models\Testimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mejoras de la portada con ideas de sitios de clínicas de referencia
 * (27/11/2026, H-114): pie con "Tu visita" y redes (sin datos sanitarios); preguntas
 * en tarjetas; llamar desde el encabezado; barra de promoción y calificación.
 */
class PortadaReferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_pie_tiene_la_columna_tu_visita(): void
    {
        PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí.', 'activa' => true, 'orden' => 1]);
        config(['dentissa.contacto.direccion' => 'Av. Prueba 10']);

        $pie = str($this->get('/')->assertOk()->getContent())->after('<footer id="contacto"')->toString();

        $this->assertStringContainsString('data-pie-tu-visita', $pie);
        $this->assertStringContainsString('>Tu visita<', $pie);
        $this->assertStringContainsString('href="'.route('citas.publica.create').'"', $pie);
        $this->assertStringContainsString('href="'.route('promociones.index').'"', $pie);
        $this->assertStringContainsString('href="#preguntas"', $pie);
        $this->assertStringContainsString('href="#ubicacion"', $pie);
    }

    /** Decisión del usuario: el pie no lleva responsable sanitario ni clave COFEPRIS, aunque haya cédula. */
    public function test_el_pie_no_muestra_responsable_sanitario_ni_cofepris(): void
    {
        User::factory()->create(['rol' => RolUsuario::SuperAdmin, 'nombre' => 'Melissa', 'apellido' => 'López', 'cedula_profesional' => '1234567']);

        $this->get('/')->assertOk()
            ->assertDontSee('Responsable sanitario')
            ->assertDontSee('COFEPRIS')
            ->assertDontSee('1234567');
    }

    public function test_las_redes_solo_salen_si_tienen_enlace(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-pie-redes', false);

        config(['dentissa.contacto.instagram' => 'https://www.instagram.com/consultorio_prueba']);

        $this->get('/')->assertOk()
            ->assertSee('data-pie-redes', false)
            ->assertSee('href="https://www.instagram.com/consultorio_prueba"', false)
            ->assertSee('aria-label="Instagram (se abre en otra pestaña)"', false)
            ->assertDontSee('aria-label="Facebook (se abre en otra pestaña)"', false);
    }

    public function test_la_doctora_guarda_redes_con_enlaces_validos(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
        $base = ['telefono' => '7551131833', 'mismo_whatsapp' => '1'];

        $this->actingAs($admin)->put(route('admin.consultorio.update'), $base + ['instagram' => 'https://example.test/perfil'])
            ->assertSessionHasErrors('instagram');

        $this->actingAs($admin)->put(route('admin.consultorio.update'), $base + [
            'instagram' => 'https://www.instagram.com/consultorio',
            'facebook' => 'https://www.facebook.com/consultorio',
            'tiktok' => 'https://www.tiktok.com/@consultorio',
        ])->assertSessionHasNoErrors();

        $fila = DatosConsultorio::fila();
        $this->assertSame('https://www.tiktok.com/@consultorio', $fila->tiktok);

        DatosConsultorio::aplicarAConfiguracion();
        $this->assertSame('https://www.facebook.com/consultorio', config('dentissa.contacto.facebook'));

        $this->actingAs($admin)->get(route('admin.configuracion'))->assertOk()
            ->assertDontSee('COFEPRIS')
            ->assertSee('Redes sociales (opcional)')
            ->assertSee('value="https://www.instagram.com/consultorio"', false);
    }

    public function test_las_preguntas_frecuentes_van_en_tarjetas(): void
    {
        PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí.', 'activa' => true, 'orden' => 1]);

        $this->get('/')->assertOk()
            ->assertSee('class="group rounded-2xl border border-line bg-white', false)
            ->assertDontSee('divide-y divide-line/80 border-y border-line/80 lg:col-span-7', false);
    }

    public function test_el_encabezado_tiene_boton_de_llamar_en_celular(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('data-llamar-encabezado', false)
            ->assertSee('aria-label="Llamar al consultorio: '.config('dentissa.contacto.telefono').'"', false);
    }

    public function test_la_barra_de_promocion_sale_solo_con_promocion_vigente(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-barra-promocion', false);

        Oferta::create(['titulo' => 'Limpieza de temporada', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 15,
            'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addDays(3)->toDateString(), 'cupo_total' => 10, 'estado' => 'activa']);
        Oferta::create(['titulo' => 'Promo larga', 'tipo' => 'descuento', 'tipo_valor' => 'porcentaje', 'valor_descuento' => 10,
            'fecha_inicio' => now()->subDay()->toDateString(), 'fecha_fin' => now()->addMonth()->toDateString(), 'cupo_total' => 10, 'estado' => 'activa']);

        $fin = now()->addDays(3)->endOfDay()->getTimestamp() * 1000;

        $this->get('/')->assertOk()
            ->assertSee('data-barra-promocion', false)
            ->assertSee('Limpieza de temporada')   // la que termina primero
            ->assertSee('fin: '.$fin, false)
            ->assertSee('href="'.route('promociones.index').'"', false)
            ->assertSee('aria-label="Cerrar aviso de promoción"', false);
    }

    public function test_opiniones_muestran_la_calificacion_promedio(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-calificacion-promedio', false);

        foreach ([5, 4, 5] as $i => $estrellas) {
            $paciente = Paciente::create(['nombre' => "Ana{$i}", 'apellido' => 'Prueba', 'telefono' => '55 1234 567'.$i, 'email' => "ana{$i}@example.test"]);
            Testimonio::create(['id_paciente' => $paciente->id, 'calificacion' => $estrellas, 'comentario' => 'Muy buena atención en todo.', 'estado' => 'aprobado', 'fecha_publicacion' => now()]);
        }
        $pendiente = Paciente::create(['nombre' => 'Luis', 'apellido' => 'Prueba', 'telefono' => '55 1234 5699', 'email' => 'luis@example.test']);
        Testimonio::create(['id_paciente' => $pendiente->id, 'calificacion' => 1, 'comentario' => 'Aún sin revisar por la clínica.', 'estado' => 'pendiente']);

        $this->get('/')->assertOk()
            ->assertSee('data-calificacion-promedio', false)
            ->assertSee('4.7</strong> de 5 · promedio de 3 opiniones', false);
    }
}
