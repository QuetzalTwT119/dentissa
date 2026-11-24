<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Http\Controllers\WhatsappWebhookController;
use App\Models\DatosConsultorio;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 22/11/2026: el teléfono del consultorio solo se cambiaba con variables de
 * Railway. Si la doctora perdía el celular o cambiaba de número, dependía de
 * alguien con acceso al servidor. Ahora se cambia en Configuración.
 */
class DatosConsultorioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => RolUsuario::SuperAdmin]);
    }

    private function guardar(User $usuario, array $datos)
    {
        $respuesta = $this->actingAs($usuario)->put(route('admin.consultorio.update'), $datos + ['mismo_whatsapp' => '1']);
        // Lo guardado se aplica al arrancar; en la prueba se vuelve a aplicar a mano.
        DatosConsultorio::aplicarAConfiguracion();

        return $respuesta;
    }

    public function test_sin_datos_guardados_usa_los_de_las_variables(): void
    {
        $this->actingAs($this->admin())->get(route('admin.configuracion'))->assertOk()
            ->assertSee('value="755 113 1833"', false)
            ->assertSee('No es el número del bot');
    }

    public function test_la_doctora_cambia_el_telefono_y_se_ve_en_todos_lados(): void
    {
        $this->guardar($this->admin(), ['telefono' => '755 222 3344', 'correo' => 'consultorio@example.test', 'direccion' => 'Av. Juárez 10, Zihuatanejo'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.configuracion').'#consultorio');

        $this->assertSame('755 222 3344', config('dentissa.contacto.telefono'));
        $this->assertSame('tel:+527552223344', config('dentissa.contacto.telefono_enlace'));
        // "Es el mismo número": el WhatsApp de contacto sale del teléfono.
        $this->assertSame('527552223344', config('dentissa.contacto.whatsapp'));
        $this->assertSame('consultorio@example.test', config('dentissa.contacto.correo'));

        $this->get('/')->assertOk()->assertSee('755 222 3344')->assertDontSee('755 113 1833');
        $this->assertStringContainsString('755 222 3344', WhatsappWebhookController::textoAutomatico());
    }

    public function test_whatsapp_distinto_y_validacion(): void
    {
        $admin = $this->admin();

        $this->guardar($admin, ['telefono' => '123', 'mismo_whatsapp' => '0', 'whatsapp' => ''])
            ->assertSessionHasErrors(['telefono', 'whatsapp']);
        $this->guardar($admin, ['telefono' => '7552223344', 'correo' => 'no-es-correo'])
            ->assertSessionHasErrors('correo');
        $this->assertNull(DatosConsultorio::first());

        $this->guardar($admin, ['telefono' => '7552223344', 'mismo_whatsapp' => '0', 'whatsapp' => '755-999-8877'])
            ->assertSessionHasNoErrors();
        $this->assertSame('527559998877', config('dentissa.contacto.whatsapp'));
    }

    public function test_solo_el_administrador_y_queda_en_auditoria(): void
    {
        $asistente = User::factory()->create(['rol' => RolUsuario::Asistente]);
        $this->actingAs($asistente)->get(route('admin.configuracion'))->assertForbidden();
        $this->actingAs($asistente)->put(route('admin.consultorio.update'), ['telefono' => '7552223344', 'mismo_whatsapp' => '1'])->assertForbidden();

        $this->guardar($this->admin(), ['telefono' => '7552223344']);

        $registro = LogAuditoria::where('accion', 'consultorio.actualizado')->firstOrFail();
        $this->assertSame('755 222 3344', $registro->datos_nuevos['telefono']);
    }

    public function test_el_formulario_pide_confirmacion_y_esta_en_el_menu(): void
    {
        $this->actingAs($this->admin())->get(route('admin.configuracion'))->assertOk()
            ->assertSee('data-confirmar-titulo="¿Guardar los datos del consultorio?"', false)
            ->assertSee('Datos del consultorio');
    }

    /** 22/11/2026: Configuración es una sola página con los dos bloques. */
    public function test_configuracion_es_una_sola_pagina_con_sus_bloques(): void
    {
        $admin = $this->admin();

        $html = $this->actingAs($admin)->get(route('admin.configuracion'))->assertOk()
            ->assertSee('id="consultorio"', false)
            ->assertSee('id="horarios"', false)
            ->assertSee('Guardar horarios')
            ->getContent();
        // Dos formularios: guardar uno nunca guarda el otro.
        $this->assertSame(1, substr_count($html, 'action="'.route('admin.consultorio.update').'"'));
        $this->assertSame(1, substr_count($html, 'action="'.route('admin.horarios.update').'"'));

        // Las direcciones de antes llevan a su bloque; el menú ya no es desplegable.
        $this->actingAs($admin)->get(route('admin.horarios.edit'))->assertRedirect('/admin/configuracion#horarios');
        $this->actingAs($admin)->get(route('admin.consultorio.edit'))->assertRedirect('/admin/configuracion#consultorio');
        $this->assertStringContainsString('href="'.route('admin.configuracion').'"', $html);
    }
    /** 24/11/2026: Misión y Visión de la portada se cambian aquí; vacías vuelven al texto de fábrica. */
    public function test_mision_y_vision_se_editan_y_salen_en_la_portada(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.configuracion'))->assertOk()
            ->assertSee('Misión (portada)')->assertSee('Visión (portada)');

        $this->guardar($admin, ['telefono' => '7551131833', 'mision' => 'Sonrisas sanas sin prisas.', 'vision' => 'Ser el consultorio de confianza de Pozos.'])
            ->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('Sonrisas sanas sin prisas.')->assertSee('Ser el consultorio de confianza de Pozos.');

        $this->guardar($admin, ['telefono' => '7551131833', 'mision' => str_repeat('a', 401)])->assertSessionHasErrors('mision');
    }
}
