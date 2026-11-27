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

    public function test_ia_solo_selecciona_una_fuente_y_no_puede_redactar_hechos(): void
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
                        'fuente_id' => 'faq:'.$fuente->id,
                        'respuesta' => 'El modelo no debe poder inyectar este dato falso.',
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Dónde queda el consultorio?');

        $this->assertSame([
            'encontrada' => true,
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'pregunta' => '¿Cuál es la dirección del consultorio?',
        ], $resultado);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://ollama:11434/api/chat'
            && $request['model'] === 'granite3.3:2b'
            && $request['format'] === 'json'
            && str_contains($request['messages'][0]['content'], 'No redactes ni modifiques respuestas')
            && str_contains($request['messages'][1]['content'], 'Avenida Ejemplo 123'));
    }

    public function test_descarta_una_fuente_ia_que_no_existe(): void
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
                        'fuente_id' => 'faq:99999',
                    ]),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Dónde queda el consultorio?');

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

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Dónde queda el consultorio?');

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
                        'fuente_id' => 'catalogo:servicio:'.$servicio->id,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Ofrecen Odontología preventiva?');

        $this->assertSame('Catálogo: Odontología preventiva', $resultado['pregunta'] ?? null);
        $this->assertSame('Servicio disponible: Odontología preventiva. Tratamientos asociados disponibles: Limpieza de rutina', $resultado['respuesta'] ?? null);
        Http::assertSent(fn (Request $request) => str_contains($request['messages'][1]['content'], 'Limpieza de rutina')
            && ! str_contains($request['messages'][1]['content'], 'Tratamiento retirado'));
    }

    public function test_descarta_una_fuente_valida_que_no_tiene_relacion_con_la_consulta(): void
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

        Http::preventStrayRequests();

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cuál es el color favorito de la doctora?');

        $this->assertNull($resultado);
        Http::assertNothingSent();
    }

    public function test_no_puede_inventar_precios_ni_usar_una_faq_ajena(): void
    {
        PreguntaFrecuente::create([
            'pregunta' => '¿Esta información es oficial de Dentissa?',
            'respuesta' => 'No. Es una respuesta temporal de QA.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama:11434',
        ]);
        Http::preventStrayRequests();

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cuánto cuesta un implante dental?');

        $this->assertNull($resultado);
        Http::assertNothingSent();
    }

    public function test_no_envia_peticiones_de_medicamentos_a_ollama(): void
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

        foreach ([
            '¿Qué antibiótico debo tomar para el dolor de muela?',
            'Me duele una muela, ¿qué puedo hacer?',
        ] as $consulta) {
            $this->assertNull(app(AsistenteIaLocalService::class)->responder($consulta));
        }

        Http::assertNothingSent();
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
                        'fuente_id' => 'faq:'.$fuente->id,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $this->postJson(route('asistente-paciente.responder'), [
            'consulta' => '¿Dónde queda el consultorio?',
            'conversacion_id' => '00000000-0000-4000-8000-000000000021',
        ])->assertOk()
            ->assertJsonPath('encontrada', true)
            ->assertJsonPath('pregunta', '¿Cuál es la dirección del consultorio?')
            ->assertJsonPath('respuesta', 'El consultorio está en Avenida Ejemplo 123.');
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
            ->assertJsonFragment(['respuesta' => "Claro. ¿Qué tipo de atención necesitas?\n\n\n\nSi quieres salir, escribe \"cancelar\"."]);

        Http::assertNothingSent();
    }

    /** 27/11/2026: antes se quedaba en el paso de agenda; ahora responde la FAQ y cierra la búsqueda. */
    public function test_con_busqueda_abierta_una_faq_cierra_la_busqueda_y_se_responde(): void
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
            ->assertJsonFragment(['respuesta' => 'El consultorio está en Avenida Ejemplo 123.']);

        // La búsqueda quedó cerrada: lo siguiente ya no se toma como respuesta del paso.
        $this->assertFalse(app(\App\Services\AsistenteAgendaService::class)->tieneConsultaEnCurso('web:'.$conversacion));

        Http::assertNothingSent();
    }

    public function test_puede_usar_ollama_por_el_dominio_privado_de_railway(): void
    {
        $fuente = PreguntaFrecuente::create([
            'pregunta' => '¿Cuál es la dirección del consultorio?',
            'respuesta' => 'El consultorio está en Avenida Ejemplo 123.',
            'activa' => true,
        ]);
        config([
            'dentissa.asistente_ia.habilitada' => true,
            'dentissa.asistente_ia.ollama_url' => 'http://ollama.railway.internal:11434',
        ]);

        Http::fake([
            'ollama.railway.internal:11434/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode(['fuente_id' => 'faq:'.$fuente->id], JSON_UNESCAPED_UNICODE),
                ],
            ]),
        ]);

        $resultado = app(AsistenteIaLocalService::class)->responder('¿Cuál es la dirección del consultorio?');

        $this->assertSame('El consultorio está en Avenida Ejemplo 123.', $resultado['respuesta'] ?? null);
        Http::assertSent(fn (Request $request) => $request->url() === 'http://ollama.railway.internal:11434/api/chat');
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
