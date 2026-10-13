<?php

namespace Tests\Feature;

use App\Models\PreguntaFrecuente;
use App\Models\Servicio;
use App\Models\Tratamiento;
use App\Services\AsistenteIaLocalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsistenteIaLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_ia_local_esta_apagada_por_defecto_y_no_hace_solicitudes(): void
    {
        Http::preventStrayRequests();

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cómo llego a la clínica?');

        $this->assertNull($resultado);
        Http::assertNothingSent();
    }

    public function test_ia_redacta_solo_si_devuelve_una_fuente_aprobada_en_su_contexto(): void
    {
        $fuente = PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
            'dentissa.asistente_ia.ollama_modelo' => 'granite3.3:2b',
        ]);

        Http::fake([
            'ollama:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'encontrada' => true,
                        'respuesta' => 'Estamos en Avenida Ejemplo 123.',
                        'fuentes' => ['faq:'.$fuente->id],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cómo llego a la clínica?');

        $this->assertSame([
            'encontrada' => true,
            'respuesta' => 'Estamos en Avenida Ejemplo 123.',
            'pregunta' => '¿Cuál es la dirección del consultorio?',
        ], $resultado);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://ollama:11434/api/chat'
            && $request['model'] === 'granite3.3:2b'
            && $request['format'] === 'json'
            && str_contains($request['messages'][0]['content'], 'No diagnostiques')
            && str_contains($request['messages'][1]['content'], 'Avenida Ejemplo 123'));
    }

    public function test_descarta_una_respuesta_ia_sin_referencia_a_fuentes_aprobadas(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);

        Http::fake([
            'ollama:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'encontrada' => true,
                        'respuesta' => 'La clínica abre a las 9.',
                        'fuentes' => ['faq:99999'],
                    ]),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿A qué hora abren?');

        $this->assertNull($resultado);
    }

    public function test_usa_fallback_si_ollama_reporta_error(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);
        Http::fake([
            'ollama:11434/api/chat' => Http::response([], 503),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cómo llego a la clínica?');

        $this->assertNull($resultado);
    }

    public function test_usa_el_catalogo_activo_sin_requerir_otra_faq(): void
    {
        $servicio = Servicio::create(['nombre' => 'Odontología preventiva', 'activo' => true]);
        $activo = Tratamiento::create([
            'nombre' => 'Limpieza de rutina',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => true,
        ]);
        $inactivo = Tratamiento::create([
            'nombre' => 'Tratamiento retirado',
            'duracion_estimada' => 30,
            'categoria' => 'Preventiva',
            'activo' => false,
        ]);
        $servicio->tratamientos()->attach([$activo->id, $inactivo->id]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);

        Http::fake([
            'ollama:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'encontrada' => true,
                        'respuesta' => 'Está disponible Odontología preventiva, incluida la limpieza de rutina.',
                        'fuentes' => ['catalogo:servicio:'.$servicio->id],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Qué tipo de servicios ofrecen?');

        $this->assertSame('Catálogo: Odontología preventiva', $resultado['pregunta'] ?? null);
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'Limpieza de rutina')
            && ! str_contains($request['messages'][1]['content'], 'Tratamiento retirado'));
    }

    public function test_chat_web_usa_ia_solo_despues_de_faq_y_flujos_controlados(): void
    {
        $fuente = PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);

        Http::fake([
            'ollama:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'encontrada' => true,
                        'respuesta' => 'Estamos en Avenida Ejemplo 123.',
                        'fuentes' => ['faq:'.$fuente->id],
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cómo puedo llegar hasta ustedes?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000021',
        ])->assertOk()
            ->assertJsonPath('encontrada', true)
            ->assertJsonPath('pregunta', '¿Cuál es la dirección del consultorio?')
            ->assertJsonPath('respuesta', 'Estamos en Avenida Ejemplo 123.');
    }

    public function test_chat_no_consulta_ia_para_iniciar_una_agenda(): void
    {
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);
        Http::preventStrayRequests();

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero una cita',
            'conversacion_id' => '00000000-0000-4000-8000-000000000022',
        ])->assertOk()
            ->assertJsonPath('encontrada', false)
            ->assertJsonPath('accion', null)
            ->assertJsonFragment(['respuesta' => "Claro. ¿Qué tipo de atención necesitas?\n\n"]);

        Http::assertNothingSent();
    }

    public function test_chat_no_consulta_ia_para_atencion_urgente(): void
    {
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);
        Http::preventStrayRequests();

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Tengo dolor intenso y necesito ayuda',
            'conversacion_id' => '00000000-0000-4000-8000-000000000023',
        ])->assertOk()
            ->assertJsonPath('accion.etiqueta', 'Solicitar atención urgente');

        Http::assertNothingSent();
    }

    public function test_chat_respeta_el_paso_actual_de_agenda_aunque_el_texto_coincida_con_una_faq(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);
        Http::preventStrayRequests();
        $conversacion = '00000000-0000-4000-8000-000000000024';

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => 'Quiero una cita',
            'conversacion_id' => $conversacion,
        ])->assertOk();

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Cuál es la dirección del consultorio?',
            'conversacion_id' => $conversacion,
        ])->assertOk()
            ->assertJsonFragment(['respuesta' => "No identifiqué ese tipo de atención. Escríbelo tal como aparece aquí:\n\n"]);

        Http::assertNothingSent();
    }

    public function test_no_envia_consultas_a_un_endpoint_de_ia_publico(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'https://modelo-publico.example/api',
        ]);
        Http::preventStrayRequests();

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cómo llego a la clínica?');

        $this->assertNull($resultado);
        Http::assertNothingSent();
    }
}
