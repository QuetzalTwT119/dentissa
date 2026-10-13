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
            'consulta' => '¿CUANTO CUESTA una limpieza DENTAL!',
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
                'respuesta' => 'No pude identificar con seguridad una respuesta aprobada. Intenta escribir la duda con otras palabras o comunícate con el consultorio para orientarte.',
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
                'respuesta' => 'No pude identificar con seguridad una respuesta aprobada. Intenta escribir la duda con otras palabras o comunícate con el consultorio para orientarte.',
            ]);
    }

    public function test_acepta_variantes_aprobadas_para_parafrasis(): void
    {
        $admin = User::factory()->create(['rol' => RolUsuario::Admin]);

        $this->actingAs($admin)
            ->get(route('admin.preguntas-frecuentes.create'))
            ->assertOk()
            ->assertSee('Otras formas de preguntar (opcional)')
            ->assertSee('una forma alternativa por línea');

        $this->actingAs($admin)
            ->post(route('admin.preguntas-frecuentes.store'), [
                'pregunta' => '¿Cuánto cuesta una limpieza dental?',
                'respuesta' => 'La respuesta aprobada se muestra sin inventar datos.',
                'variantes' => "¿Cuál es el precio de limpieza dental?\nDime el costo de una limpieza dental",
                'orden' => 0,
                'activa' => '1',
            ])
            ->assertRedirect(route('admin.preguntas-frecuentes.index'));

        $this->assertDatabaseHas('preguntas_frecuentes', [
            'pregunta' => '¿Cuánto cuesta una limpieza dental?',
            'variantes' => "¿Cuál es el precio de limpieza dental?\nDime el costo de una limpieza dental",
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Me dicen el precio para limpieza dental',
            'conversacion_id' => '00000000-0000-4000-8000-000000000013',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => true,
                'respuesta' => 'La respuesta aprobada se muestra sin inventar datos.',
            ]);
    }

    public function test_tolera_un_error_de_escritura_leve_sin_cambiar_la_respuesta(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuánto cuesta una limpieza dental?',
            'respuesta' => 'Respuesta aprobada.',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuánto cuesta una limpiesa dental?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000014',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => true,
                'respuesta' => 'Respuesta aprobada.',
            ]);
    }

    public function test_no_responde_por_una_sola_palabra_compartida(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Esta información es oficial de Dentissa?',
            'respuesta' => 'Respuesta que no debe elegirse por una palabra accidental.',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Dentissa odontología tratamientos',
            'conversacion_id' => '00000000-0000-4000-8000-000000000015',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => false,
                'respuesta' => 'No pude identificar con seguridad una respuesta aprobada. Intenta escribir la duda con otras palabras o comunícate con el consultorio para orientarte.',
            ]);
    }

    public function test_no_elige_una_faq_cuando_dos_resultados_son_igual_de_probables(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Qué horario hay para limpieza dental?',
            'respuesta' => 'Respuesta del horario.',
            'activa' => true,
        ]);
        PreguntaFrecuente::create([
            'pregunta' => '¿Qué costo tiene limpieza dental?',
            'respuesta' => 'Respuesta del costo.',
            'activa' => true,
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Horario o costo de limpieza dental?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000016',
        ])
            ->assertOk()
            ->assertJson([
                'encontrada' => false,
                'respuesta' => 'No pude identificar con seguridad una respuesta aprobada. Intenta escribir la duda con otras palabras o comunícate con el consultorio para orientarte.',
            ]);
    }
}
