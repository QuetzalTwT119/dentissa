<?php

namespace Tests\Feature;

use App\Models\PreguntaFrecuente;
use App\Services\AsistenteAgendaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión del usuario en producción (27/11/2026, H-115): mapa en el mar,
 * hueco junto a las preguntas, fallas del asistente y botones flotantes.
 */
class MapaPreguntasAsistenteTest extends TestCase
{
    use RefreshDatabase;

    private function preguntar(string $consulta, string $conversacion = '00000000-0000-4000-8000-000000000115')
    {
        return $this->postJson(route('asistente-paciente.responder'), ['consulta' => $consulta, 'conversacion_id' => $conversacion])->assertOk();
    }

    /** Con "Dentissa" en el nombre de Maps, Google mandaba el mapa al mar. */
    public function test_el_mapa_y_como_llegar_usan_solo_la_direccion(): void
    {
        $direccion = 'Juan Francisco Noyola 2443, Cultura Purépecha, 60950 Cdad. Lázaro Cárdenas, Mich.';
        config(['dentissa.contacto.direccion' => $direccion, 'dentissa.contacto.nombre_maps' => 'Dentissa']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('maps.google.com/maps?q='.urlencode($direccion).'&amp;z=16', $html);
        $this->assertStringContainsString('destination='.urlencode($direccion).'"', $html);
        $this->assertStringNotContainsString('q='.urlencode('Dentissa, '.$direccion).'&amp;z=16', $html);
        $this->assertStringContainsString('data-ficha-maps', $html);
    }

    /** En escritorio el título de las preguntas se queda a la vista y trae los dos caminos de ayuda. */
    public function test_las_preguntas_tienen_titulo_fijo_y_botones_de_ayuda(): void
    {
        PreguntaFrecuente::create(['pregunta' => '¿Atienden niños?', 'respuesta' => 'Sí.', 'activa' => true, 'orden' => 1]);

        $this->get('/')->assertOk()
            ->assertSee('class="lg:sticky lg:top-28" data-preguntas-encabezado', false)
            ->assertSee('data-preguntas-whatsapp', false)
            ->assertSee("x-on:click=\"\$dispatch('abrir-asistente')\"", false);
    }

    /** "¿Cuál es su horario?" empezaba una búsqueda de citas en lugar de dar el horario. */
    public function test_cual_es_su_horario_da_el_horario_y_no_empieza_a_agendar(): void
    {
        foreach (['¿Cuál es su horario?', '¿qué horario tienen?'] as $consulta) {
            $respuesta = $this->preguntar($consulta, fake()->uuid())->json('respuesta');

            $this->assertStringStartsWith('Nuestro horario de atención:', $respuesta);
            $this->assertStringNotContainsString('¿Qué tipo de atención necesitas?', $respuesta);
        }

        // "Horarios libres para mañana" sí sigue siendo búsqueda.
        $this->assertStringNotContainsString('Nuestro horario de atención', $this->preguntar('quiero ver horarios libres para mañana', fake()->uuid())->json('respuesta'));
    }

    /** "Me duele una muela" respondía "No pude identificar"; ahora va a urgencias y cierra la búsqueda. */
    public function test_dolor_va_a_urgencias_aunque_haya_una_busqueda_abierta(): void
    {
        PreguntaFrecuente::create(['pregunta' => '¿Atienden urgencias?', 'respuesta' => 'Llámanos al 755 113 1833 y te damos un espacio.', 'activa' => true, 'orden' => 1]);
        $conversacion = '00000000-0000-4000-8000-000000000116';

        $this->preguntar('quiero una cita', $conversacion);
        $this->assertTrue(app(AsistenteAgendaService::class)->tieneConsultaEnCurso('web:'.$conversacion));

        $this->preguntar('me duele una muela desde ayer, ¿qué hago?', $conversacion)
            ->assertJsonPath('encontrada', true)
            ->assertJsonPath('respuesta', 'Llámanos al 755 113 1833 y te damos un espacio.');

        $this->assertFalse(app(AsistenteAgendaService::class)->tieneConsultaEnCurso('web:'.$conversacion));
    }

    /** Sin pregunta de urgencias aprobada, se da el teléfono, nunca una indicación clínica. */
    public function test_dolor_sin_pregunta_de_urgencias_da_el_telefono(): void
    {
        $respuesta = $this->preguntar('se me hinchó la encía', fake()->uuid())->json('respuesta');

        $this->assertStringContainsString(config('dentissa.contacto.telefono'), $respuesta);
    }
}
