<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\PreguntaFrecuente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreguntaFrecuenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_admin_puede_publicar_una_faq_y_el_asistente_responde_con_su_texto_aprobado(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.preguntas-frecuentes.store'), [
                'pregunta' => '¿Cuánto cuesta una limpieza dental?',
                'respuesta' => 'El consultorio confirma el costo vigente antes de la cita.',
                'orden' => 0,
                'activa' => '1',
            ])
            ->assertRedirect(route('admin.preguntas-frecuentes.index'));

        $this->assertDatabaseHas('preguntas_frecuentes', [
            'pregunta' => '¿Cuánto cuesta una limpieza dental?',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuánto cuesta una limpieza dental?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000010',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => true,
                'respuesta' => 'El consultorio confirma el costo vigente antes de la cita.',
            ]);
    }

    public function test_desmarcar_activa_despublica_la_faq_y_el_asistente_usa_el_fallback(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);
        $pregunta = PreguntaFrecuente::create([
            'pregunta' => '¿Cuánto cuesta una limpieza dental?',
            'respuesta' => 'El consultorio confirma el costo vigente antes de la cita.',
            'activa' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.preguntas-frecuentes.update', $pregunta), [
                'pregunta' => $pregunta->pregunta,
                'respuesta' => $pregunta->respuesta,
                'orden' => 0,
            ])
            ->assertRedirect(route('admin.preguntas-frecuentes.index'));

        $this->assertDatabaseHas('preguntas_frecuentes', [
            'id' => $pregunta->id,
            'activa' => false,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuánto cuesta una limpieza dental?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000011',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => false,
                'respuesta' => 'No encontré una respuesta aprobada para esa duda. Por favor comunícate con el consultorio para orientarte.',
            ]);
    }

    public function test_el_admin_puede_crear_una_faq_desactivada_sin_publicarla(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->post(route('admin.preguntas-frecuentes.store'), [
                'pregunta' => '¿Cuánto cuesta una limpieza dental?',
                'respuesta' => 'El consultorio confirma el costo vigente antes de la cita.',
                'orden' => 0,
            ])
            ->assertRedirect(route('admin.preguntas-frecuentes.index'));

        $pregunta = PreguntaFrecuente::firstOrFail();
        $this->assertFalse($pregunta->activa);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuánto cuesta una limpieza dental?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000012',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => false,
                'respuesta' => 'No encontré una respuesta aprobada para esa duda. Por favor comunícate con el consultorio para orientarte.',
            ]);
    }
}
